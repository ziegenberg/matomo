<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Reports;

use Matomo\DbHelper;
use Matomo\Matomo;
use Matomo\Plugin\ReportsProvider;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\PageUrl;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetPageUrls extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new PageUrl();
        $this->name          = Matomo::translate('Actions_PageUrls');
        $this->documentation = Matomo::translate('Actions_PagesReportDocumentation', '<br />')
                             . '<br />' . Matomo::translate('General_UsePlusMinusIconsDocumentation');

        $this->actionToLoadSubTables = $this->action;
        $this->order   = 2;
        $this->metrics = array('nb_hits', 'nb_visits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );

        $this->subcategoryId = 'General_Pages';
        $this->hasGoalMetrics = true;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig($factory->createWidget()->setName($this->subcategoryId));
    }

    public function getMetrics()
    {
        $metrics = parent::getMetrics();
        $metrics['nb_visits'] = Matomo::translate('General_ColumnUniquePageviews');

        return $metrics;
    }

    protected function getMetricsDocumentation()
    {
        $metrics = parent::getMetricsDocumentation();
        $metrics['nb_visits'] = Matomo::translate('General_ColumnUniquePageviewsDocumentation');
        $metrics['bounce_rate'] = Matomo::translate('General_ColumnPageBounceRateDocumentation');

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display = array('label', 'nb_hits', 'nb_visits', 'bounce_rate',
                                                  'avg_time_on_page', 'exit_rate');

        if (version_compare(DbHelper::getInstallVersion(), '4.0.0-b1', '<')) {
            $view->config->columns_to_display[] = 'avg_time_generation';
        }

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);

        $view->config->show_goals = true;

        // related reports are only shown on performance page
        if ($view->requestConfig->getRequestParam('performance') !== '1') {
            $view->config->related_reports = [];
        }
    }

    public function getRelatedReports()
    {
        return [
            ReportsProvider::factory('Actions', 'getEntryPageUrls'),
            ReportsProvider::factory('Actions', 'getExitPageUrls'),
        ];
    }
}
