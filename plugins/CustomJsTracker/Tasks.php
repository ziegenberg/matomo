<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomJsTracker;

use Matomo\Container\StaticContainer;

class Tasks extends \Matomo\Plugin\Tasks
{
    public function schedule()
    {
        $this->hourly('updateTracker');
    }

    public function updateTracker()
    {
        $updater = StaticContainer::get('Matomo\Plugins\CustomJsTracker\TrackerUpdater');
        $updater->update();
    }
}
