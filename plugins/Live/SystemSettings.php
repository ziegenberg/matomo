<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Live;

use Matomo\Matomo;
use Matomo\Settings\FieldConfig;
use Matomo\Settings\Plugin\SystemSetting;
use Matomo\Plugins\Live\Settings\VisitorLogDisabled as VisitorLogDisabledSetting;

class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var SystemSetting|null */
    public $disableVisitorLog;

    /** @var SystemSetting|null */
    public $disableVisitorProfile;

    protected function init()
    {
        $this->disableVisitorLog     = $this->makeVisitorLogSetting();
        $this->disableVisitorProfile = $this->makeVisitorProfileSetting();
    }

    private function makeVisitorLogSetting(): SystemSetting
    {
        $setting = VisitorLogDisabledSetting::getSystemSetting();
        $setting->setConfigureCallback(function (FieldConfig $field) {
            $field->title = VisitorLogDisabledSetting::getTitle();
            $field->inlineHelp = VisitorLogDisabledSetting::getInlineHelp();
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });

        $this->addSetting($setting);
        return $setting;
    }

    private function makeVisitorProfileSetting(): SystemSetting
    {
        $defaultValue = false;
        $type = FieldConfig::TYPE_BOOL;

        return $this->makeSetting('disable_visitor_profile', $defaultValue, $type, function (FieldConfig $field) {
            $field->title = Matomo::translate('Live_DisableVisitorProfile');
            $field->inlineHelp = Matomo::translate('Live_DisableVisitorProfileDescription');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            $field->condition = 'disable_visitor_log==0';
        });
    }
}
