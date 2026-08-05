<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Settings\Interfaces\Traits\Setters;

use Matomo\Settings\Interfaces\Traits\Getters\SystemGetterTrait;

/**
 * @template T of mixed
 */
trait SystemSetterTrait
{
    /**
     * @use SystemGetterTrait<T>
     */
    use SystemGetterTrait;

    /**
     * @param T $value
     */
    public static function setSystemValue($value): void
    {
        $setting = self::getSystemSetting();
        $setting->setValue($value);
        $setting->save();
    }
}
