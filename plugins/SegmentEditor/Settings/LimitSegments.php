<?php

namespace Matomo\Plugins\SegmentEditor\Settings;

use Matomo\Matomo;
use Matomo\Plugins\PrivacyManager\Settings\CompliancePolicyEnforcedSetting;
use Matomo\Policy\CnilPolicy;

class LimitSegments extends CompliancePolicyEnforcedSetting
{
    public static function getTitle(): string
    {
        return Matomo::translate("SegmentEditor_LimitSegmentsSettingTitle");
    }

    public static function getPolicyRequirements(): array
    {
        return [
            CnilPolicy::class => true,
        ];
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate("SegmentEditor_LimitSegmentsSettingRequirementNote");
    }
}
