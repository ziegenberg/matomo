<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Plugins\Installation\ServerFilesGenerator;
use Matomo\Updater;
use Matomo\Updates as PiwikUpdates;

class Updates_3_0_1_b1 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
        // Allow IIS to serve .woff files (https://github.com/piwik/piwik/pull/11091).
        // Re-generate .htaccess without 'Options -Indexes' because it does not always work on some servers
        ServerFilesGenerator::createFilesForSecurity();
    }
}
