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

class Updates_2_4_0_b6 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        $pluginManager = \Matomo\Plugin\Manager::getInstance();

        try {
            $pluginManager->activatePlugin('DevicesDetection');
        } catch (\Exception $e) {
        }
    }
}
