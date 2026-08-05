<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PrivacyManager\Settings;

use Matomo\Matomo;
use Matomo\Plugins\PrivacyManager\Config;
use Matomo\Settings\Interfaces\CustomSettingInterface;
use Matomo\Settings\Interfaces\PolicyComparisonInterface;
use Matomo\Settings\Interfaces\SettingValueInterface;
use Matomo\Settings\Interfaces\Traits\Getters\CustomGetterTrait;
use Matomo\Settings\Interfaces\Traits\PolicyComparisonTrait;
use Matomo\Policy\CnilPolicy;

/**
 * @implements CustomSettingInterface<int|null>
 * @implements PolicyComparisonInterface<int|null>
 * @implements SettingValueInterface<int|null>
 */
class IPAnonymisation implements CustomSettingInterface, PolicyComparisonInterface, SettingValueInterface
{
    /**
     * @use PolicyComparisonTrait<int|null>
     */
    use PolicyComparisonTrait;

    /**
     * @use CustomGetterTrait<int|null>
     */
    use CustomGetterTrait;

    private ?int $value;

    private function __construct(?int $value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    protected static function getCustomSettingName(): string
    {
        return 'ipAnonymizerEnabled';
    }

    public static function getCustomValue(?int $idSite = null)
    {
        // disallowing compliance override to prevent indefinite loop in getting the value
        return (new Config($idSite))->getFromOption(self::getCustomSettingName(), $allowPolicyComplianceOverride = false);
    }

    public static function getTitle(): string
    {
        return Matomo::translate('PrivacyManager_AnonymizeIpPolicySettingTitle');
    }

    public static function getComplianceRequirementNote(?int $idSite = null): string
    {
        // TODO add dynamic messaging
        return Matomo::translate('PrivacyManager_AnonymizeIpPolicySettingRequirementNote');
    }

    public static function getInlineHelp(): string
    {
        return Matomo::translate('PrivacyManager_AnonymizeIpInlineHelp');
    }

    public static function getPolicyRequirements(): array
    {
        $policies = [];
        $policies[CnilPolicy::class] = 1;

        return $policies;
    }

    public static function getInstance(?int $idSite = null): self
    {
        $values = self::getPolicyRequiredValues($idSite);
        $customValue = self::getCustomValue($idSite);
        $values['custom'] = isset($customValue) ? (int) $customValue : null;

        $x = self::getStrictestValueFromArray($values);

        return new self($x);
    }

    public static function isCompliant(string $policy, ?int $idSite = null): bool
    {
        $policyValues = self::getPolicyRequirements();

        if (!array_key_exists($policy, $policyValues)) {
            return true;
        }

        $currentValue = self::getInstance($idSite)->getValue();

        return $currentValue >= $policyValues[$policy];
    }

    protected static function compareStrictness($value1, $value2)
    {
        if ($value1 > $value2) {
            return $value1;
        }
        return $value2;
    }
}
