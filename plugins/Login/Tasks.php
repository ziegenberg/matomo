<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login;

use Matomo\Plugins\Login\Security\BruteForceDetection;

class Tasks extends \Matomo\Plugin\Tasks
{
    private BruteForceDetection $bruteForceDetection;

    public function __construct(BruteForceDetection $bruteForceDetection)
    {
        $this->bruteForceDetection = $bruteForceDetection;
    }

    public function schedule()
    {
        $this->daily('cleanupBruteForceLogs');
    }

    public function cleanupBruteForceLogs()
    {
        $this->bruteForceDetection->cleanupOldEntries();
    }
}
