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
use Matomo\Plugins\BotTracking\Columns\Metrics\ClickThroughRate;
use Matomo\Plugins\BotTracking\Metrics;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\CoreVisualizations\Visualizations\Sparklines;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class Get extends Report
{
    protected function init(): void
    {
        parent::init();
        $this->categoryId       = 'General_AIAssistants';
        $this->subcategoryId    = 'BotTracking_AIChatbotsOverview';
        $this->name             = Matomo::translate('BotTracking_ReportTitleChatbotsOverview');
        $this->documentation    = '';
        $this->metrics          = Metrics::getReportMetricColumns();
        $this->processedMetrics = [
            new ClickThroughRate(),
        ];
        $this->order            = 10;

        if (\Matomo\Request::fromRequest()->getStringParameter('period', '') !== 'day') {
            $this->metrics = array_filter($this->metrics, function ($metric) {
                return !in_array($metric, [Metrics::METRIC_AI_CHATBOTS_UNIQUE_DOCUMENT_URLS, Metrics::METRIC_AI_CHATBOTS_UNIQUE_PAGE_URLS]);
            });
        }
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory): void
    {
        $widgetsList->addWidgetConfig(
            $factory->createWidget()
                ->setName('BotTracking_ReportTitleChatbotsOverTime')
                ->forceViewDataTable(Evolution::ID)
                ->setAction('getEvolutionGraph')
                ->setOrder(1)
        );

        $widgetsList->addWidgetConfig(
            $factory->createWidget()
                ->setName('BotTracking_ReportTitleChatbotsOverview')
                ->forceViewDataTable(Sparklines::ID)
                ->setOrder(2)
        );
    }

    public function configureView(ViewDataTable $view): void
    {
        if (!$view->isViewDataTableId(Sparklines::ID)) {
            return;
        }

        /** @var Sparklines $view */
        $view->config->title = Matomo::translate('BotTracking_ReportTitleChatbotsOverview');
        $view->config->addTranslations(Metrics::getMetricTranslations());
        $view->config->metrics_documentation = Metrics::getMetricDocumentation();

        $order = 0;
        foreach (Metrics::getSparklineMetricOrder() as $metric) {
            if (
                \Matomo\Request::fromRequest()->getStringParameter('period', '') !== 'day'
                && in_array($metric, [Metrics::METRIC_AI_CHATBOTS_UNIQUE_DOCUMENT_URLS, Metrics::METRIC_AI_CHATBOTS_UNIQUE_PAGE_URLS])
            ) {
                continue;
            }
            $view->config->addSparklineMetric($metric, $order++);
        }

        SegmentNotSupportedMessageHelper::addSegmentNotSupportedMessage($view);
    }
}
