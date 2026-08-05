<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager;

use Matomo\Matomo;
use Matomo\Plugins\UsersManager\Validators\AllowedEmailDomain;
use Matomo\Settings\Setting;
use Matomo\Settings\FieldConfig;

/**
 * Defines Settings for UsersManager.
 */
class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $allowedEmailDomains;

    /** @var Setting */
    public $enableInactiveUsersNotifications;

    protected function init()
    {
        $this->allowedEmailDomains = $this->createAllowedEmailDomains();
        $this->enableInactiveUsersNotifications = $this->createEnableInactiveUsersNotifications();
    }

    private function createEnableInactiveUsersNotifications(): Setting
    {
        return $this->makeSetting('enableInactiveUsersNotifications', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Matomo::translate('UsersManager_SettingEnableInactiveUsersNotifications');
            $field->description = Matomo::translate('UsersManager_SettingEnableInactiveUsersNotificationsHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    private function createAllowedEmailDomains()
    {
        return $this->makeSetting('allowedEmailDomains', array(), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = Matomo::translate('UsersManager_SettingRestrictLoginEmailDomains');
            $field->uiControl = FieldConfig::UI_CONTROL_FIELD_ARRAY;
            $title = Matomo::translate('UsersManager_SettingFieldAllowedEmailDomain');
            $arrayField = new FieldConfig\ArrayField($title, FieldConfig::UI_CONTROL_TEXT);
            $field->uiControlAttributes['field'] = $arrayField->toArray();
            $field->description = Matomo::translate('UsersManager_SettingRestrictLoginEmailDomainsHelp');

            $allowedEmailDomains = new AllowedEmailDomain($this);
            $domainsInUse = $allowedEmailDomains->getEmailDomainsInUse();
            $field->inlineHelp .= '<br><strong>' . Matomo::translate('UsersManager_SettingRestrictLoginEmailDomainsHelpInUse') . '</strong>';
            $field->inlineHelp .= '<br>' . implode('<br>', $domainsInUse);

            $field->validate = function ($value) use ($field, $allowedEmailDomains) {
                $value = call_user_func($field->transform, $value, $this);

                if (empty($value)) {
                    return;
                }

                $domainsInUse = $allowedEmailDomains->getEmailDomainsInUse();
                $notMatchingDomains = array_diff($domainsInUse, $value);

                if (!empty($notMatchingDomains)) {
                    $notMatchingDomains = implode(', ', array_unique($notMatchingDomains));
                    $message = Matomo::translate('UsersManager_SettingRestrictLoginEmailDomainsErrorOtherDomainsInUse', $notMatchingDomains);
                    throw new \Exception($message);
                }
            };
            $field->transform = function ($domains) {
                if (empty($domains)) {
                    return array();
                }

                if (!is_array($domains)) {
                    $domains = [$domains];
                }

                $domains = array_map(function ($domain) {
                    $domain = trim($domain);
                    if (mb_strpos($domain, '@') !== false) {
                        // handle incorrect user input such as leading @ or entered email address
                        $allowedEmailDomains = new AllowedEmailDomain($this);
                        $domain = $allowedEmailDomains->getDomainFromEmail($domain);
                    }
                    return mb_strtolower($domain);
                }, $domains);
                $domains = array_filter($domains, 'strlen');
                $domains = array_unique($domains);
                $domains = array_values($domains);
                return $domains;
            };
        });
    }
}
