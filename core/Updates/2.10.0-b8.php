<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Updates;
use Matomo\Updater;

class Updates_2_10_0_b8 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        $pluginManager = \Matomo\Plugin\Manager::getInstance();

        try {
            $pluginManager->activatePlugin('Resolution');
        } catch (\Exception $e) {
        }
    }
}
