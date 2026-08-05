<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login\Commands;

use Matomo\API\Request;
use Matomo\Matomo;
use Matomo\Plugin\ConsoleCommand;

class UnblockBlockedIps extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('login:unblock-blocked-ips');
        $this->setDescription('Unblocks all currently blocked IPs. Useful if you cannot log in to your Matomo anymore because your own IP is blocked');
    }

    protected function doExecute(): int
    {
        Request::processRequest('Login.unblockBruteForceIPs');
        $message = sprintf('<info>%s</info>', Matomo::translate('General_Done'));

        $this->getOutput()->writeln($message);

        return self::SUCCESS;
    }
}
