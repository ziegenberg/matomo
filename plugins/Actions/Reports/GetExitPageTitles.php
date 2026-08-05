<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Actions\Columns\ExitPageTitle;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetExitPageTitles extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new ExitPageTitle();
        $this->name          = Matomo::translate('Actions_ExitPageTitles');
        $this->documentation = Matomo::translate('Actions_ExitPageTitlesReportDocumentation', '<br />')
                             . ' ' . Matomo::translate('General_UsePlusMinusIconsDocumentation');
        $this->subcategoryId = 'Actions_SubmenuPagesExit';

        $this->metrics = array('exit_nb_visits', 'nb_visits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->order = 7;

        $this->actionToLoadSubTables = $this->action;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        // we have to do it manually since it's only done automatically if a subcategoryId is specified,
        // we do not set a subcategoryId since this report is not supposed to be shown in the UI
        $widgetsList->addWidgetConfig($factory->createWidget());
    }

    public function getProcessedMetrics()
    {
        $result = parent::getProcessedMetrics();

        // these metrics are not displayed in the API.getProcessedReport version of this report,
        // so they are removed here.
        unset($result['bounce_rate']);
        unset($result['avg_time_on_page']);

        return $result;
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

        unset($metrics['bounce_rate']);
        unset($metrics['avg_time_on_page']);

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->title = $this->name;
        $view->config->columns_to_display = array('label', 'exit_nb_visits', 'nb_visits', 'exit_rate');

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getPageTitles'),
            ReportsProvider::factory('Actions', 'getExitPageUrls'),
        );
    }
}
