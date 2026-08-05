<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Filesystem;
use Matomo\Tracker\Cache;
use Matomo\Updates;
use Matomo\Updater;

/**
 * Update for version 2.5.0-rc4.
 */
class Updates_2_5_0_rc4 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        Cache::deleteTrackerCache();
        Filesystem::clearPhpCaches();
    }
}
