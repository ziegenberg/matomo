<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\PrivacyManager\Settings;

use Matomo\Matomo;
use Matomo\Policy\CnilPolicy;

class DataRoundingEnabled extends CompliancePolicyEnforcedSetting
{
    public static function getTitle(): string
    {
        return Matomo::translate('PrivacyManager_SegmentedDataRoundingSettingTitle');
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate('PrivacyManager_SegmentedDataRoundingSettingRequirementNote');
    }

    public static function getPolicyRequirements(): array
    {
        return [
            CnilPolicy::class => true,
        ];
    }
}
