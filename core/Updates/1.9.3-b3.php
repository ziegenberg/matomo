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

class Updates_1_9_3_b3 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        // Insight was a temporary code name for Overlay
        $pluginToDelete = 'Insight';
        self::deletePluginFromConfigFile($pluginToDelete);
        \Matomo\Plugin\Manager::getInstance()->deletePluginFromFilesystem($pluginToDelete);

        // We also clean up 1.9.1 and delete Feedburner plugin
        \Matomo\Plugin\Manager::getInstance()->deletePluginFromFilesystem('Feedburner');
    }
}
