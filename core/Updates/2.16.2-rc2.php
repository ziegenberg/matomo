<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Updater;
use Matomo\Updates as PiwikUpdates;

/**
 * Update for version 2.16.2-rc2
 */
class Updates_2_16_2_rc2 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        try {
            \Matomo\Plugin\Manager::getInstance()->activatePlugin('ProfessionalServices');
        } catch (\Exception $e) {
        }

        try {
            \Matomo\Plugin\Manager::getInstance()->deactivatePlugin('PiwikPro');
            self::deletePluginFromConfigFile('PiwikPro');
        } catch (\Exception $e) {
        }
    }
}
