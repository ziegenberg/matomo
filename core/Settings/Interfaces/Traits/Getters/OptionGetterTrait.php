<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Settings\Interfaces\Traits\Getters;

use Matomo\Option;
use Matomo\Settings\Interfaces\OptionSettingInterface;

/**
 * @phpstan-require-implements OptionSettingInterface
 */
trait OptionGetterTrait
{
    public static function getOptionValue(?int $idSite = null): ?string
    {
        $optionValue = Option::get(self::getOptionName($idSite));
        if ($optionValue !== false) {
            return $optionValue;
        }
        return null;
    }

    abstract protected static function getOptionName(?int $idSite = null): string;

    /**
     * @deprecated Will be removed in 6.0 in favour of making getOptionName public
     */
    public static function getOptionSettingShortName(): string
    {
        return self::getOptionName();
    }
}
