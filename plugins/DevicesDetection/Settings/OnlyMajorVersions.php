<?php

namespace Matomo\Plugins\DevicesDetection\Settings;

use Matomo\Matomo;
use Matomo\Policy\CnilPolicy;
use Matomo\Settings\Interfaces\PolicyComparisonInterface;
use Matomo\Settings\Interfaces\SettingValueInterface;
use Matomo\Settings\Interfaces\Traits\PolicyComparisonTrait;

/**
 * @implements PolicyComparisonInterface<bool>
 * @implements SettingValueInterface<bool>
 */
class OnlyMajorVersions implements
    PolicyComparisonInterface,
    SettingValueInterface
{
    /** @use PolicyComparisonTrait<bool> */
    use PolicyComparisonTrait;

    private bool $value;

    protected function __construct(bool $value)
    {
        $this->value = $value;
    }

    protected static function compareStrictness($value1, $value2)
    {
        return $value1 || $value2;
    }

    public static function getTitle(): string
    {
        return Matomo::translate("DevicesDetection_OnlyMajorVersionsSettingTitle");
    }

    public static function getInstance(?int $idSite = null): self
    {
        $values = self::getPolicyRequiredValues($idSite);

        $strictest = (bool) self::getStrictestValueFromArray($values);
        return new self($strictest);
    }

    public static function getInlineHelp(): string
    {
        return '';
    }

    public static function getPolicyRequirements(): array
    {
        return [
            CnilPolicy::class => true,
        ];
    }

    public static function isCompliant(string $policy, ?int $idSite = null): bool
    {
        $policyValues = self::getPolicyRequirements();
        if (!array_key_exists($policy, $policyValues)) {
            return true;
        }

        $currentValue = self::getInstance($idSite)->getValue();

        return $currentValue === $policyValues[$policy];
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate("DevicesDetection_OnlyMajorVersionsSettingRequirementNote");
    }
}
