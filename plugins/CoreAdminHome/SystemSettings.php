<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome;

use Matomo\Matomo;
use Matomo\Plugins\CoreAdminHome\Controller as CoreAdminController;
use Matomo\Settings\Setting;
use Matomo\Settings\FieldConfig;
use Matomo\Tracker\Cache;

class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $corsDomains;

    /** @var Setting */
    public $trustedHostnames;

    protected function init()
    {
        $this->title = ' '; // intentionally left blank as it's hidden with css

        $isWritable = Matomo::hasUserSuperUserAccess() && CoreAdminController::isGeneralSettingsAdminEnabled();
        $this->trustedHostnames = $this->createTrustedHostnames();
        $this->trustedHostnames->setIsWritableByCurrentUser($isWritable);

        $isWritable = Matomo::hasUserSuperUserAccess();
        $this->corsDomains = $this->createCorsDomains();
        $this->corsDomains->setIsWritableByCurrentUser($isWritable);
    }


    private function createCorsDomains()
    {
        return $this->makeSettingManagedInConfigOnly('General', 'cors_domains', $default = [], FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->introduction = Matomo::translate('CoreAdminHome_CorsDomains');
            $field->uiControl = FieldConfig::UI_CONTROL_FIELD_ARRAY;
            $arrayField = new FieldConfig\ArrayField(Matomo::translate('Overlay_Domain'), FieldConfig::UI_CONTROL_TEXT);
            $field->uiControlAttributes['field'] = $arrayField->toArray();
            $field->inlineHelp = Matomo::translate('CoreAdminHome_CorsDomainsHelp');
            $field->transform = function ($values) {
                return array_values(array_filter($values));
            };
        });
    }

    private function createTrustedHostnames()
    {
        return $this->makeSettingManagedInConfigOnly('General', 'trusted_hosts', $default = [], FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->introduction = Matomo::translate('CoreAdminHome_TrustedHostSettings');
            $field->uiControl = FieldConfig::UI_CONTROL_FIELD_ARRAY;
            $arrayField = new FieldConfig\ArrayField(Matomo::translate('CoreAdminHome_ValidPiwikHostname'), FieldConfig::UI_CONTROL_TEXT);
            $field->uiControlAttributes['field'] = $arrayField->toArray();
            $field->transform = function ($values) {
                return array_values(array_filter($values));
            };
        });
    }

    public function save()
    {
        parent::save();
        Cache::deleteTrackerCache();
    }
}
