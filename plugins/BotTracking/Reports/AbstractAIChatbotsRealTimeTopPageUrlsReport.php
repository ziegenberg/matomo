<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\BotTracking\Columns\Metrics\Requests;
use Matomo\Plugins\BotTracking\Columns\PageUrl;
use Matomo\Plugins\BotTracking\Dao\BotRequestsDao;
use Matomo\Plugins\BotTracking\Metrics;

abstract class AbstractAIChatbotsRealTimeTopPageUrlsReport extends AbstractAIChatbotsRealTimeReport
{
    protected function init(): void
    {
        parent::init();

        $this->dimension = new PageUrl();
        $this->metrics   = [
            new Requests(),
        ];
    }

    public function configureView(ViewDataTable $view): void
    {
        parent::configureView($view);

        $view->config->setDefaultColumnsToDisplay(
            ['label', Metrics::COLUMN_REQUESTS],
            false,
            false
        );
    }

    protected function getReportRowLimit(): int
    {
        return BotRequestsDao::getAIChatbotTopPageUrlsForDateRangeLimit();
    }
}
