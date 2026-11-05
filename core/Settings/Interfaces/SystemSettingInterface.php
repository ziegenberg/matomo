<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Settings\Interfaces;

use Matomo\Settings\Plugin\SystemSetting;

/**
 * @template T of mixed
 */
interface SystemSettingInterface
{
    public static function getSystemSetting(): SystemSetting;

    /**
     * @return T
     */
    public static function getSystemValue();
}
