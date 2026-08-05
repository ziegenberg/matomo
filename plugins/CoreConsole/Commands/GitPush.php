<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreConsole\Commands;

use Matomo\Development;
use Matomo\Plugin\ConsoleCommand;
use Matomo\SettingsPiwik;

class GitPush extends ConsoleCommand
{
    public function isEnabled()
    {
        return Development::isEnabled() && SettingsPiwik::isGitDeployment();
    }

    protected function configure()
    {
        $this->setName('git:push');
        $this->setDescription('Push Piwik repo and all submodules');
    }

    protected function doExecute(): int
    {
        $cmd = sprintf('cd %s && git push --recurse-submodules=on-demand', PIWIK_DOCUMENT_ROOT);
        $this->getOutput()->writeln('Executing command: ' . $cmd);
        passthru($cmd);

        return self::SUCCESS;
    }
}
