<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\API\Request;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;

class SegmentNotSupportedMessageHelper
{
    public static function addSegmentNotSupportedMessage(ViewDataTable $view): void
    {
        if (!empty(Request::getRawSegmentFromRequest())) {
            $message = '<p class="alert alert-info">' . Matomo::translate('BotTracking_SegmentNotSupported') . '</p>';
            $view->config->show_footer_message = $message;
        }
    }
}
