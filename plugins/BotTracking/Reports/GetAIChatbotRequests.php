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
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\BotTracking\Columns\AIChatbotName;
use Matomo\Plugins\BotTracking\Columns\Metrics\AcquiredVisits;
use Matomo\Plugins\BotTracking\Columns\Metrics\DocumentRequests;
use Matomo\Plugins\BotTracking\Columns\Metrics\PageRequests;
use Matomo\Plugins\BotTracking\Columns\Metrics\Requests;
use Matomo\Plugins\BotTracking\Metrics;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;
use Matomo\Plugins\BotTracking\Reports\SegmentNotSupportedMessageHelper;

class GetAIChatbotRequests extends Report
{
    protected function init(): void
    {
        parent::init();

        $this->name              = Matomo::translate('BotTracking_AIChatbotsReportTitle');
        $this->documentation     = Matomo::translate('BotTracking_AIChatbotsReportDocumentation');
        $this->categoryId        = 'General_AIAssistants';
        $this->subcategoryId     = 'BotTracking_AIChatbotsOverview';
        $this->dimension         = new AIChatbotName();
        $this->metrics           = [
            new Requests(),
            new PageRequests(),
            new DocumentRequests(),
            new AcquiredVisits(),
        ];
        $this->processedMetrics  = [];
        $this->order             = 30;
        $this->defaultSortColumn = Metrics::COLUMN_ACQUIRED_VISITS;
        if (\Matomo\Request::fromRequest()->getStringParameter('secondaryDimension', '') === 'documents') {
            $this->actionToLoadSubTables = 'getDocumentUrlsForAIChatbot';
        } else {
            $this->actionToLoadSubTables = 'getPageUrlsForAIChatbot';
        }
    }

    public function configureView(ViewDataTable $view): void
    {
        parent::configureView($view);

        $view->config->show_table_all_columns = false;
        $view->config->show_insights          = false;

        SegmentNotSupportedMessageHelper::addSegmentNotSupportedMessage($view);

        $view->config->setDefaultColumnsToDisplay(
            ['label', Metrics::COLUMN_REQUESTS, Metrics::COLUMN_PAGE_REQUESTS, Metrics::COLUMN_DOCUMENT_REQUESTS, Metrics::COLUMN_ACQUIRED_VISITS],
            false,
            false
        );

        // only show request count for flat table, as subtable doesn't have other metrics
        if ((int)$view->requestConfig->getRequestParam('flat') === 1) {
            $view->config->setDefaultColumnsToDisplay(
                ['label', Metrics::COLUMN_REQUESTS],
                false,
                false
            );
        }

        $secondaryDimensions = [
            'pages'     => Matomo::translate('BotTracking_ColumnPageRequests'),
            'documents' => Matomo::translate('BotTracking_ColumnDocumentRequests'),
        ];
        $view->config->setSecondaryDimensions($secondaryDimensions, 'pages');
    }

    /**
     * @return void
     */
    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig($factory->createWidget()->setIsWide());
    }
}
