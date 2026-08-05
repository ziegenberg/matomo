<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Monolog\tests\Integration\Fixture;

use Matomo\Log;

class LoggerWrapper
{
    public static function doLog($message)
    {
        Log::warning($message);
    }
}
