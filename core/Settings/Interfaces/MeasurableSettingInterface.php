<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Settings\Interfaces;

use Matomo\Settings\Measurable\MeasurableProperty;
use Matomo\Settings\Measurable\MeasurableSetting;

/**
 * @template T of mixed
 */
interface MeasurableSettingInterface
{
    /**
     * @return MeasurableSetting|MeasurableProperty
     */
    public static function getMeasurableSetting(int $idSite);

    /**
     * @return T
     */
    public static function getMeasurableValue(int $idSite);
}
