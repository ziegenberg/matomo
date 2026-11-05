<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scheduler;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\Manager as PluginManager;

/**
 * Loads scheduled tasks.
 */
class TaskLoader
{
    /**
     * @return Task[]
     */
    public function loadTasks()
    {
        $tasks = array();

        $pluginTasks = PluginManager::getInstance()->findComponents('Tasks', 'Matomo\Plugin\Tasks');

        foreach ($pluginTasks as $pluginTask) {
            $pluginTask = StaticContainer::get($pluginTask);
            $pluginTask->schedule();

            foreach ($pluginTask->getScheduledTasks() as $task) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }
}
