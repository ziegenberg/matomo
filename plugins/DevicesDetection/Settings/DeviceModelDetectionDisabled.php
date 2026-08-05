<?php

namespace Matomo\Plugins\DevicesDetection\Settings;

use Matomo\Matomo;
use Matomo\Plugins\PrivacyManager\Settings\CompliancePolicyEnforcedSetting;
use Matomo\Policy\CnilPolicy;

class DeviceModelDetectionDisabled extends CompliancePolicyEnforcedSetting
{
    public static function getTitle(): string
    {
        return Matomo::translate('DevicesDetection_DeviceModelDetectionDisabled');
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate('DevicesDetection_DeviceModelDetectionDisabledRequirementNote');
    }

    public static function getPolicyRequirements(): array
    {
        return [
            CnilPolicy::class => true,
        ];
    }
}
