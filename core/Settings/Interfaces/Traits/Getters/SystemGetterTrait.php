<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Settings\Interfaces\Traits\Getters;

use Matomo\Matomo;
use Matomo\Settings\Plugin\SystemSetting;

/**
 * @template T of mixed
 *
 * @phpstan-require-implements \Matomo\Settings\Interfaces\SystemSettingInterface<T>
 */
trait SystemGetterTrait
{
    public static function getSystemSetting(): SystemSetting
    {
        return new SystemSetting(
            self::getSystemName(),
            self::getSystemDefaultValue(),
            self::getSystemType(),
            Matomo::getPluginNameOfMatomoClass(static::class)
        );
    }

    /**
     * @return T
     */
    public static function getSystemValue()
    {
        return self::getSystemSetting()->getValue();
    }

    /**
     * @return T
     */
    abstract protected static function getSystemDefaultValue();

    abstract protected static function getSystemName(): string;

    abstract protected static function getSystemType(): string;

    /**
     * @deprecated Will be removed in 6.0 in favour of making getSystemName public
     */
    public static function getSystemSettingShortName(): string
    {
        return self::getSystemName();
    }
}
