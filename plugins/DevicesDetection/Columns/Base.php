<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicesDetection\Columns;

use Matomo\Container\StaticContainer;
use Matomo\DeviceDetector\DeviceDetectorFactory;
use Matomo\Plugin\Dimension\VisitDimension;

abstract class Base extends VisitDimension
{
    protected function getUAParser($userAgent, $clientHints)
    {
        return StaticContainer::get(DeviceDetectorFactory::class)->makeInstance($userAgent, $clientHints);
    }
}
