<?php

namespace Matomo\Tests\Framework\Mock\Settings\TraitImpls;

use Matomo\Settings\Interfaces\PolicyComparisonInterface;
use Matomo\Settings\Interfaces\Traits\PolicyComparisonTrait;
use Matomo\Tests\Framework\Mock\Policy\TestPolicy;

class PolicyComparisonTraitImpl implements PolicyComparisonInterface
{
    /**
     * @use PolicyComparisonTrait<bool>
     */
    use PolicyComparisonTrait;

    protected static function compareStrictness($value1, $value2)
    {
        return $value1;
    }

    public static function getPolicyRequirements(): array
    {
        return [
            TestPolicy::class => true,
        ];
    }

    public static function isCompliant(string $policy, ?int $idSite = null): bool
    {
        return true;
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return 'test PolicyComparisonTrait';
    }
}
