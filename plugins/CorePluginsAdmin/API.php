<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CorePluginsAdmin;

use Matomo\Cache;
use Matomo\Matomo;
use Matomo\Plugin\SettingsProvider;
use Exception;
use Matomo\Container\StaticContainer;
use Matomo\Plugins\CoreAdminHome\Emails\SettingsChangedEmail;
use Matomo\Plugins\CoreAdminHome\Emails\SecurityNotificationEmail;
use Matomo\Plugins\Marketplace\Marketplace;

/**
 * Provides API methods for reading and updating plugin settings.
 *
 * @method static \Matomo\Plugins\CorePluginsAdmin\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    private SettingsMetadata $settingsMetadata;

    private SettingsProvider $settingsProvider;

    public function __construct(SettingsProvider $settingsProvider, SettingsMetadata $settingsMetadata)
    {
        $this->settingsProvider = $settingsProvider;
        $this->settingsMetadata = $settingsMetadata;
    }

    /**
     * @internal
     * @param array<string, array<int, array{name:string, value?:mixed}>> $settingValues
     * @param string|false $passwordConfirmation
     */
    public function setSystemSettings(
        $settingValues,
        #[\SensitiveParameter]
        $passwordConfirmation = false
    ): void {
        Matomo::checkUserHasSuperUserAccess();

        $this->confirmCurrentUserPassword($passwordConfirmation);

        $pluginsSettings = $this->settingsProvider->getAllSystemSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        $sendSettingsChangedNotificationEmailPlugins = [];

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                if (!empty($settingValues[$pluginSetting->getPluginName()])) {
                    $pluginSetting->save();

                    $pluginName = $pluginSetting->getPluginName();
                    if (in_array($pluginName, array_keys(SecurityNotificationEmail::$notifyPluginList))) {
                        $sendSettingsChangedNotificationEmailPlugins[] = $pluginName;
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception(Matomo::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }

        if (count($sendSettingsChangedNotificationEmailPlugins) > 0) {
            $this->sendNotificationEmails($sendSettingsChangedNotificationEmailPlugins);
        }
    }

    /**
     * @internal
     * @param array<string, array<int, array{name:string, value?:mixed}>> $settingValues
     */
    public function setUserSettings($settingValues): void
    {
        Matomo::checkUserIsNotAnonymous();

        $pluginsSettings = $this->settingsProvider->getAllUserSettings();

        $this->settingsMetadata->setPluginSettings($pluginsSettings, $settingValues);

        try {
            foreach ($pluginsSettings as $pluginSetting) {
                if (!empty($settingValues[$pluginSetting->getPluginName()])) {
                    $pluginSetting->save();
                }
            }
        } catch (Exception $e) {
            throw new Exception(Matomo::translate('CoreAdminHome_PluginSettingsSaveFailed'));
        }
    }

    /**
     * @internal
     * @return array<int, array<string, mixed>>
     */
    public function getSystemSettings()
    {
        Matomo::checkUserHasSuperUserAccess();

        $systemSettings = $this->settingsProvider->getAllSystemSettings();

        return $this->settingsMetadata->formatSettings($systemSettings);
    }

    /**
     * @internal
     * @return array<int, array<string, mixed>>
     */
    public function getUserSettings()
    {
        Matomo::checkUserIsNotAnonymous();

        $userSettings = $this->settingsProvider->getAllUserSettings();

        return $this->settingsMetadata->formatSettings($userSettings);
    }

    /**
     * @internal
     */
    public function getNumberOfPluginUpdates(): int
    {
        try {
            Matomo::checkUserHasSuperUserAccess();

            if (!Marketplace::isMarketplaceEnabled()) {
                return 0;
            }

            $cacheKey = 'CorePluginsAdmin_NumberOfPluginUpdates';
            $cache = Cache::getLazyCache();

            if ($cache->contains($cacheKey)) {
                return $cache->fetch($cacheKey);
            }

            $marketplacePlugins = StaticContainer::get('Matomo\Plugins\Marketplace\Plugins');
            $updatesCount = count($marketplacePlugins->getPluginsHavingUpdate());
            $cache->save($cacheKey, $updatesCount, 300);

            return $updatesCount;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @param string[] $sendSettingsChangedNotificationEmailPlugins
     */
    private function sendNotificationEmails(array $sendSettingsChangedNotificationEmailPlugins): void
    {
        $pluginNames = [];
        foreach ($sendSettingsChangedNotificationEmailPlugins as $plugin) {
            $pluginNames[] = Matomo::translate(SettingsChangedEmail::$notifyPluginList[$plugin]);
        }
        $pluginNames = implode(', ', $pluginNames);

        $container = StaticContainer::getContainer();

        $email = $container->make(SettingsChangedEmail::class, array(
            'login' => Matomo::getCurrentUserLogin(),
            'emailAddress' => Matomo::getCurrentUserEmail(),
            'pluginNames' => $pluginNames,
        ));
        $email->safeSend();

        $superuserEmailAddresses = Matomo::getAllSuperUserAccessEmailAddresses();
        unset($superuserEmailAddresses[Matomo::getCurrentUserLogin()]);
        $superUserEmail = false;

        foreach ($superuserEmailAddresses as $address) {
            $superUserEmail = $superUserEmail ?: $container->make(SettingsChangedEmail::class, array(
                'login' => Matomo::translate('Installation_SuperUser'),
                'emailAddress' => $address,
                'pluginNames' => $pluginNames,
                'superuser' => Matomo::getCurrentUserLogin(),
            ));
            $superUserEmail->addTo($address);
        }

        if ($superUserEmail) {
            $superUserEmail->safeSend();
        }
    }
}
