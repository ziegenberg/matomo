<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PrivacyManager;

use Matomo\Matomo;
use Matomo\Settings\Plugin\SystemSetting;
use Matomo\Settings\FieldConfig;

/**
 * Defines Settings for PrivacyManager.
 */
class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var SystemSetting */
    public $imprintUrl;

    /** @var SystemSetting */
    public $privacyPolicyUrl;

    /** @var SystemSetting */
    public $termsAndConditionUrl;

    /** @var SystemSetting */
    public $showInEmbeddedWidgets;

    protected function init()
    {
        $this->imprintUrl = $this->createImprintUrlSetting();
        $this->privacyPolicyUrl = $this->createPrivacyPolicyUrlSetting();
        $this->termsAndConditionUrl = $this->createTermsAndConditionUrlSetting();
        $this->showInEmbeddedWidgets = $this->createShowInEmbeddedWidgetsSetting();
    }

    private function createImprintUrlSetting(): SystemSetting
    {
        return $this->makeSetting('ImprintUrl', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Matomo::translate('PrivacyManager_ImprintUrl');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Matomo::translate('PrivacyManager_ImprintUrlDescription') . ' ' .
                Matomo::translate('PrivacyManager_PrivacyPolicyUrlDescriptionSuffix', ['anonymous']);
        });
    }

    private function createPrivacyPolicyUrlSetting(): SystemSetting
    {
        return $this->makeSetting('privacyPolicyUrl', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Matomo::translate('PrivacyManager_PrivacyPolicyUrl');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Matomo::translate('PrivacyManager_PrivacyPolicyUrlDescription') . ' ' .
                Matomo::translate('PrivacyManager_PrivacyPolicyUrlDescriptionSuffix', ['anonymous']);
        });
    }

    private function createTermsAndConditionUrlSetting(): SystemSetting
    {
        return $this->makeSetting('termsAndConditionUrl', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Matomo::translate('PrivacyManager_TermsAndConditionUrl');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Matomo::translate('PrivacyManager_TermsAndConditionUrlDescription') . ' ' .
                Matomo::translate('PrivacyManager_PrivacyPolicyUrlDescriptionSuffix', ['anonymous']);
        });
    }

    private function createShowInEmbeddedWidgetsSetting(): SystemSetting
    {
        return $this->makeSetting('showInEmbeddedWidgets', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Matomo::translate('PrivacyManager_ShowInEmbeddedWidgets');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->description = Matomo::translate('PrivacyManager_ShowInEmbeddedWidgetsDescription');
        });
    }
}
