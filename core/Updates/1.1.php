<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Config;
use Matomo\Matomo;
use Matomo\Updates;
use Matomo\Updater;

class Updates_1_1 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        $config = Config::getInstance();

        try {
            $superuser = $config->superuser;
        } catch (\Exception $e) {
            return;
        }

        if (empty($superuser['login'])) {
            return;
        }

        $rootLogin = $superuser['login'];
        try {
            // throws an exception if invalid
            Matomo::checkValidLoginString($rootLogin);
        } catch (\Exception $e) {
            throw new \Exception('Superuser login name "' . $rootLogin . '" is no longer a valid format. '
                . $e->getMessage()
                . ' Edit your config/config.ini.php to change it.');
        }
    }
}
