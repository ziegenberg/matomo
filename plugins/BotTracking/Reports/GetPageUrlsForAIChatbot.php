<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\Matomo;
use Matomo\Plugin\Report;
use Matomo\Plugins\BotTracking\Columns\Metrics\Requests;
use Matomo\Plugins\BotTracking\Columns\PageUrl;

class GetPageUrlsForAIChatbot extends Report
{
    protected function init(): void
    {
        parent::init();

        $this->name             = Matomo::translate('BotTracking_AIChatbotsReportTitle');
        $this->categoryId       = 'General_AIAssistants';
        $this->metrics          = [new Requests()];
        $this->processedMetrics = [];
        $this->dimension        = new PageUrl();
        $this->isSubtableReport = true;
    }
}
