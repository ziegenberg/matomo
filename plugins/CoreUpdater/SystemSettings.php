<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreUpdater;

use Matomo\Db\Settings;
use Matomo\DbHelper;
use Matomo\Matomo;
use Matomo\Plugin\ReleaseChannels;
use Matomo\Plugins\CoreAdminHome\Controller as CoreAdminController;
use Matomo\Plugins\Marketplace\UpdateCommunication as PluginUpdateCommunication;
use Matomo\Settings\Setting;
use Matomo\Settings\FieldConfig;
use Matomo\SettingsPiwik;
use Matomo\Url;

/**
 * Defines Settings for CoreUpdater.
 *
 * Usage like this:
 * $settings = new SystemSettings();
 * $settings->metric->getValue();
 * $settings->description->getValue();
 */
class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $releaseChannel;

    /** @var Setting */
    public $sendPluginUpdateEmail;

    /** @var Setting|null */
    public $updateToUtf8mb4 = null;

    private ReleaseChannels $releaseChannels;

    public function __construct(ReleaseChannels $releaseChannels)
    {
        $this->releaseChannels = $releaseChannels;

        parent::__construct();
    }

    protected function init()
    {
        $this->title = Matomo::translate('CoreAdminHome_UpdateSettings');

        $isWritable = Matomo::hasUserSuperUserAccess() && CoreAdminController::isGeneralSettingsAdminEnabled();
        $this->releaseChannel = $this->createReleaseChannel();
        $this->releaseChannel->setIsWritableByCurrentUser($isWritable
            && SettingsPiwik::isMultiServerEnvironment() === false);

        $this->sendPluginUpdateEmail = $this->createSendPluginUpdateEmail();
        $this->sendPluginUpdateEmail->setIsWritableByCurrentUser($isWritable
            && PluginUpdateCommunication::canBeEnabled());

        $dbSettings = new Settings();
        if ($isWritable && $dbSettings->getUsedCharset() !== 'utf8mb4' && DbHelper::getDefaultCharset() === 'utf8mb4') {
            $this->updateToUtf8mb4 = $this->createUpdateToUtf8mb4();
        }
    }

    private function createReleaseChannel()
    {
        $releaseChannels = $this->releaseChannels;
        $default = 'latest_stable';

        return $this->makeSettingManagedInConfigOnly('General', 'release_channel', $default, FieldConfig::TYPE_STRING, function (FieldConfig $field) use ($releaseChannels) {

            $field->introduction = Matomo::translate('CoreAdminHome_ReleaseChannel');
            $field->uiControl = FieldConfig::UI_CONTROL_RADIO;

            $field->availableValues = array();
            foreach ($releaseChannels->getAllReleaseChannels() as $channel) {
                if (!$channel->isSelectableInSettings()) {
                    continue;
                }
                $name = $channel->getName();
                $description = $channel->getDescription();
                if (!empty($description)) {
                    $name .= ' (' . $description . ')';
                }

                $field->availableValues[$channel->getId()] = $name;
            }

            $field->validate = function ($channel) use ($releaseChannels) {
                if (!$releaseChannels->isValidReleaseChannelId($channel)) {
                    throw new \Exception('Release channel is not valid');
                }
            };

            $field->inlineHelp = Matomo::translate('CoreAdminHome_DevelopmentProcess')
                            . '<br/>'
                            . Matomo::translate(
                                'CoreAdminHome_StableReleases',
                                [Url::getExternalLinkTag('https://developer.matomo.org/guides/core-team-workflow#influencing-piwik-development'), '</a>']
                            )
                            . '<br/>'
                            . Matomo::translate('CoreAdminHome_LtsReleases');
        });
    }

    private function createSendPluginUpdateEmail()
    {
        return $this->makeSetting('enable_plugin_update_communication', $default = true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->introduction = Matomo::translate('CoreAdminHome_SendPluginUpdateCommunication');
            $field->uiControl = FieldConfig::UI_CONTROL_RADIO;
            $field->availableValues = array('1' => sprintf('%s (%s)', Matomo::translate('General_Yes'), Matomo::translate('General_Default')),
                                            '0' => Matomo::translate('General_No'));
            $field->inlineHelp = Matomo::translate('CoreAdminHome_SendPluginUpdateCommunicationHelp');
        });
    }

    private function createUpdateToUtf8mb4()
    {
        return $this->makeSetting('update_to_utf8mb4', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->introduction = Matomo::translate('CoreUpdater_ConvertToUtf8mb4');
            $field->title = Matomo::translate('CoreUpdater_TriggerDatabaseConversion');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->inlineHelp = Matomo::translate('CoreUpdater_Utf8mb4ConversionHelp', [
                '�',
                '<code>' . PIWIK_INCLUDE_PATH . '/console core:convert-to-utf8mb4</code>',
                Url::getExternalLinkTag('https://matomo.org/faq/how-to-update/how-to-convert-the-database-to-utf8mb4-charset/'),
                '</a>',
            ]);
        });
    }
}
