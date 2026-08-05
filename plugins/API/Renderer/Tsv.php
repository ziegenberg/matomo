<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API\Renderer;

use Matomo\Common;

class Tsv extends Csv
{
    public function renderSuccess($message)
    {
        Common::sendHeader("Content-Disposition: attachment; filename=piwik-report-export.csv");
        return "message\t" . $message;
    }
}
