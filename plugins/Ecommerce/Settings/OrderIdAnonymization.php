<?php

namespace Matomo\Plugins\Ecommerce\Settings;

use Matomo\Matomo;
use Matomo\Plugins\PrivacyManager\Config;
use Matomo\Policy\CnilPolicy;
use Matomo\Settings\Interfaces\CustomSettingInterface;
use Matomo\Settings\Interfaces\PolicyComparisonInterface;
use Matomo\Settings\Interfaces\SettingValueInterface;
use Matomo\Settings\Interfaces\Traits\Getters\CustomGetterTrait;
use Matomo\Settings\Interfaces\Traits\PolicyComparisonTrait;

/**
 * @implements CustomSettingInterface<bool>
 * @implements PolicyComparisonInterface<bool>
 * @implements SettingValueInterface<bool>
 */
class OrderIdAnonymization implements
    CustomSettingInterface,
    PolicyComparisonInterface,
    SettingValueInterface
{
    /** @use PolicyComparisonTrait<bool> */
    use PolicyComparisonTrait;

    /** @use CustomGetterTrait<bool> */
    use CustomGetterTrait;

    private bool $value;

    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    protected static function getCustomSettingName(): string
    {
        return 'anonymizeOrderId';
    }

    public static function getCustomValue(?int $idSite = null)
    {
        return (new Config($idSite))->getFromOption(self::getCustomSettingName(), $allowPolicyComplianceOverride = false);
    }

    public static function getTitle(): string
    {
        return Matomo::translate('Ecommerce_OrderIdAnonymizationSettingTitle');
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        return Matomo::translate('Ecommerce_OrderIdAnonymizationSettingRequirementNote');
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

    public static function getInstance(?int $idSite = null): self
    {
        $values = self::getPolicyRequiredValues($idSite);
        $values['system'] = (bool) self::getCustomValue();
        $values['measurable'] = is_null($idSite) ? null : (bool) self::getCustomValue($idSite);

        $strictest = (bool) self::getStrictestValueFromArray($values);
        return new self($strictest);
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

    protected static function compareStrictness($value1, $value2)
    {
        return ($value1 || $value2);
    }
}
