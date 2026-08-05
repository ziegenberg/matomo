<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login;

use Matomo\Network\IP;
use Matomo\Matomo;
use Matomo\Settings\Setting;
use Matomo\Settings\FieldConfig;
use Matomo\Validators\IpRanges;
use Matomo\Auth\PasswordStrength;

/**
 * Defines Settings for Login.
 */
class SystemSettings extends \Matomo\Settings\Plugin\SystemSettings
{
    /** @var Setting */
    public $enableBruteForceDetection;

    /** @var Setting */
    public $whitelisteBruteForceIps;

    /** @var Setting */
    public $blacklistedBruteForceIps;

    /** @var Setting */
    public $maxFailedLoginsPerMinutes;

    /** @var Setting */
    public $loginAttemptsTimeRange;

    /** @var Setting */
    public $enablePasswordStrengthCheck;

    protected function init()
    {
        $this->enableBruteForceDetection = $this->createEnableBruteForceDetection();
        $this->maxFailedLoginsPerMinutes = $this->createMaxFailedLoginsPerMinutes();
        $this->loginAttemptsTimeRange = $this->createLoginAttemptsTimeRange();
        $this->blacklistedBruteForceIps = $this->createBlacklistedBruteForceIps();
        $this->whitelisteBruteForceIps = $this->createWhitelisteBruteForceIps();
        $this->enablePasswordStrengthCheck = $this->createEnablePasswordStrengthCheck();
    }

    private function createEnablePasswordStrengthCheck(): Setting
    {
        return $this->makeSetting('enablePasswordStrengthCheck', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingPasswordStrengthCheck');
            $PasswordStrengthChecker = new PasswordStrength($featureEnabled = true);
            $field->inlineHelp = Matomo::translate('Login_SettingPasswordStrengthCheckHelp', [$PasswordStrengthChecker->getRulesAsHtmlList()]);
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    private function createEnableBruteForceDetection()
    {
        return $this->makeSetting('enableBruteForceDetection', $default = true, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingBruteForceEnable');
            $field->description = Matomo::translate('Login_SettingBruteForceEnableHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }

    private function createWhitelisteBruteForceIps()
    {
        return $this->makeSetting('whitelisteBruteForceIps', array(), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingBruteForceWhitelistIp');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            $field->description = Matomo::translate('Login_HelpIpRange', array('1.2.3.4/24', '1.2.3.*', '1.2.*.*')) . ' ' . Matomo::translate('Login_NotAllowListTakesPrecendence');
            $field->validators[] = new IpRanges();
            $field->transform = function ($value) {
                if (empty($value)) {
                    return array();
                }

                $ips = array_map('trim', $value);
                $ips = array_filter($ips, 'strlen');
                $ips = array_values($ips);
                return $ips;
            };
        });
    }

    private function createBlacklistedBruteForceIps()
    {
        return $this->makeSetting('blacklistedBruteForceIps', array(), FieldConfig::TYPE_ARRAY, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingBruteForceBlacklistIp');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXTAREA;
            $field->description = Matomo::translate('Login_HelpIpRange', array('1.2.3.4/24', '1.2.3.*', '1.2.*.*')) . ' ' . Matomo::translate('Login_NotAllowListTakesPrecendence');
            $field->validators[] = new IpRanges();
            $field->transform = function ($value) {
                if (empty($value)) {
                    return array();
                }

                $ips = array_map('trim', $value);
                $ips = array_filter($ips, 'strlen');
                $ips = array_values($ips);
                return $ips;
            };
        });
    }

    private function createMaxFailedLoginsPerMinutes()
    {
        return $this->makeSetting('maxAllowedRetries', 20, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingBruteForceMaxFailedLogins');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            $field->description = Matomo::translate('Login_SettingBruteForceMaxFailedLoginsHelp');
        });
    }

    private function createLoginAttemptsTimeRange()
    {
        return $this->makeSetting('allowedRetriesTimeRange', 60, FieldConfig::TYPE_INT, function (FieldConfig $field) {
            $field->title = Matomo::translate('Login_SettingBruteForceTimeRange');
            $field->description = Matomo::translate('Login_SettingBruteForceTimeRangeHelp');
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
        });
    }

    public function isWhitelistedIp($ipAddress)
    {
        return $this->isIpInList($ipAddress, $this->whitelisteBruteForceIps->getValue());
    }

    public function isBlacklistedIp($ipAddress)
    {
        return $this->isIpInList($ipAddress, $this->blacklistedBruteForceIps->getValue());
    }

    private function isIpInList($ipAddress, $ips)
    {
        if (empty($ipAddress)) {
            return false;
        }

        $ip = IP::fromStringIP($ipAddress);

        if (empty($ips)) {
            return false;
        }

        return $ip->isInRanges($ips);
    }
}
