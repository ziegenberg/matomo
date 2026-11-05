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

/**
 * Update for version 2.10.0-b4.
 */
class Updates_2_10_0_b4 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        $pluginManager = \Matomo\Plugin\Manager::getInstance();

        try {
            $pluginManager->activatePlugin('BulkTracking');
        } catch (\Exception $e) {
        }
    }
}
