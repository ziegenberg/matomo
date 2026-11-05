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

class Updates_2_0_b10 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        parent::deletePluginFromConfigFile('Referers');
        parent::deletePluginFromConfigFile('PDFReports');
    }
}
