<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreConsole\Commands;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\ConsoleCommand;

class WatchLog extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('log:watch');
        $this->setDescription('Outputs the last parts of the log files and follows as the log file grows. Does not work on Windows');
    }

    protected function doExecute(): int
    {
        $path = StaticContainer::get('path.tmp') . '/logs/';
        $cmd = sprintf('tail -f %s*.log', $path);

        $this->getOutput()->writeln('Executing command: ' . $cmd);
        passthru($cmd);

        return self::SUCCESS;
    }
}
