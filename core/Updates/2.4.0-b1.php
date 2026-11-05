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

class Updates_2_4_0_b1 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        try {
            \Matomo\Plugin\Manager::getInstance()->activatePlugin('Morpheus');
        } catch (\Exception $e) {
        }

        try {
            \Matomo\Plugin\Manager::getInstance()->deactivatePlugin('Zeitgeist');
            self::deletePluginFromConfigFile('Zeitgeist');
        } catch (\Exception $e) {
        }
    }
}
