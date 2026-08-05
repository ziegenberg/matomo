<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Marketplace\tests\Framework\Mock;

use Matomo\Cache\Backend\NullCache;
use Matomo\Cache\Lazy;
use Matomo\Log\NullLogger;

class Client
{
    public static function build($service)
    {
        $environment = new Environment();
        return new \Matomo\Plugins\Marketplace\Api\Client($service, new Lazy(new NullCache()), new NullLogger(), $environment);
    }
}
