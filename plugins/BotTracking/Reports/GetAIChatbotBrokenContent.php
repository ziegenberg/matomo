<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\DataTable;
use Matomo\Matomo;
use Matomo\Plugin\Report;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\BotTracking\Columns\ContentUrl;
use Matomo\Plugins\BotTracking\Columns\Metrics\PageNotFound404Requests;
use Matomo\Plugins\BotTracking\Columns\Metrics\ServerError5xxRequests;
use Matomo\Plugins\BotTracking\Columns\Metrics\TotalBrokenRequests;
use Matomo\Plugins\BotTracking\Metrics;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetAIChatbotBrokenContent extends Report
{
    protected function init(): void
    {
        parent::init();

        $this->name              = Matomo::translate('BotTracking_AIChatbotsBrokenContentTitle');
        $this->documentation     = Matomo::translate('BotTracking_AIChatbotsBrokenContentDocumentation');
        $this->categoryId        = 'General_AIAssistants';
        $this->subcategoryId     = 'BotTracking_AIChatbotsContentRequests';
        $this->dimension         = new ContentUrl();
        $this->metrics           = [new TotalBrokenRequests(), new ServerError5xxRequests(), new PageNotFound404Requests()];
        $this->processedMetrics  = [];
        $this->order             = 30;
        $this->defaultSortColumn = Metrics::COLUMN_TOTAL_BROKEN_REQUESTS;
    }

    public function configureView(ViewDataTable $view): void
    {
        parent::configureView($view);

        $view->config->setDefaultColumnsToDisplay(
            [
                'label',
                Metrics::COLUMN_TOTAL_BROKEN_REQUESTS,
                Metrics::COLUMN_PAGE_NOT_FOUND_404_REQUESTS,
                Metrics::COLUMN_SERVER_ERROR_5XX_REQUESTS,
            ],
            false,
            false
        );

        // Show-all-columns switches to the Visitor Engagement preset, which doesn't fit this schema.
        $view->config->show_table_all_columns = false;

        // Insights and bar/pie/tag-cloud all assume visit metrics (nb_visits etc.) this report lacks,
        // so they would render empty — the table is the only useful view.
        $view->config->show_insights = false;
        $view->config->show_bar_chart = false;
        $view->config->show_pie_chart = false;
        $view->config->show_tag_cloud = false;

        // Render URL labels as clickable links (scheme-less normalized URLs; prepend https://).
        $view->config->filters[] = function (DataTable $table) {
            foreach ($table->getRows() as $row) {
                if ($row->isSummaryRow()) {
                    continue;
                }
                $label = $row->getColumn('label');
                if (is_string($label) && $label !== '') {
                    $row->setMetadata('url', 'https://' . $label);
                }
            }
        };

        SegmentNotSupportedMessageHelper::addSegmentNotSupportedMessage($view);
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory): void
    {
        // Rendered non-wide so it pairs side by side with the Documents report on the
        // Content Requests page (consecutive non-wide widgets are grouped into two columns).
        $widgetsList->addWidgetConfig($factory->createWidget());
    }
}
