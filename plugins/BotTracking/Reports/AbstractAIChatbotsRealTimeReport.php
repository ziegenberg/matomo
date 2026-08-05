<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\BotTracking\Reports;

use Matomo\Plugin\Report;
use Matomo\Plugin\ViewDataTable;
use Matomo\Matomo;
use Matomo\Plugins\BotTracking\Metrics;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

abstract class AbstractAIChatbotsRealTimeReport extends Report
{
    protected function init(): void
    {
        parent::init();

        $this->categoryId        = 'General_AIAssistants';
        $this->subcategoryId     = 'BotTracking_AIChatbotsRealtime';
        $this->processedMetrics  = [];
        $this->defaultSortColumn = Metrics::COLUMN_REQUESTS;
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        return false;
    }

    public function configureView(ViewDataTable $view): void
    {
        parent::configureView($view);

        $view->requestConfig->filter_sort_column = $this->defaultSortColumn;
        $view->requestConfig->filter_sort_order  = 'desc';
        $view->config->show_footer_message        = Matomo::translate(
            'BotTracking_AIChatbotsRealTimeReportLimitFooter',
            [$this->getReportRowLimit()]
        );

        $view->config->metrics_documentation = array_merge(
            $view->config->metrics_documentation,
            $this->getMetricsDocumentation()
        );

        $view->config->show_table_all_columns = false;
        $view->config->show_insights          = false;
        $view->config->show_bar_chart         = false;
        $view->config->show_pie_chart         = false;
        $view->config->show_tag_cloud         = false;
        $view->config->disable_row_evolution  = true;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory): void
    {
        foreach ($this->getWidgetDefinitions() as $definition) {
            $widget = $factory->createWidget()
                ->setName($definition['name'])
                ->setOrder($definition['order'])
                ->setParameters(['lastMinutes' => $definition['lastMinutes']])
                ->setIsWide();

            $widgetsList->addWidgetConfig($widget);
        }
    }

    /**
     * @return array<int, array{name: string, documentation: string, lastMinutes: int, order: int}>
     */
    abstract protected function getWidgetDefinitions(): array;

    abstract protected function getReportRowLimit(): int;
}
