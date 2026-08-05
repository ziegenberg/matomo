<?php

namespace Matomo\Plugins\Resolution\Settings;

use Matomo\Matomo;
use Matomo\Plugins\PrivacyManager\Settings\CompliancePolicyEnforcedSetting;
use Matomo\Policy\CnilPolicy;

class ScreenResolutionDetectionDisabled extends CompliancePolicyEnforcedSetting
{
    public static function getTitle(): string
    {
        return Matomo::translate('Resolution_ScreenResolutionDetectionDisabled');
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate('Resolution_ScreenResolutionDetectionDisabledRequirementNote');
    }

    public static function getPolicyRequirements(): array
    {
        return [
            CnilPolicy::class => true,
        ];
    }
}
