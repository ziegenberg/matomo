<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Matomo;
use Matomo\Updates;
use Matomo\Updater;

class Updates_0_4_4 extends Updates
{
    public function doUpdate(Updater $updater)
    {
        $obsoleteFile = PIWIK_DOCUMENT_ROOT . '/libs/open-flash-chart/php-ofc-library/ofc_upload_image.php';
        if (file_exists($obsoleteFile)) {
            $rc = @unlink($obsoleteFile);
            if (!$rc) {
                throw new \Exception(Matomo::translate('General_ExceptionUndeletableFile', array($obsoleteFile)));
            }
        }
    }
}
