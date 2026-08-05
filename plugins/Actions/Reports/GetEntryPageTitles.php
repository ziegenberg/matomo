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
use Matomo\Plugins\Actions\Columns\EntryPageTitle;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetEntryPageTitles extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new EntryPageTitle();
        $this->name          = Matomo::translate('Actions_EntryPageTitles');
        $this->documentation = Matomo::translate('Actions_EntryPageTitlesReportDocumentation', '<br />')
                             . ' ' . Matomo::translate('General_UsePlusMinusIconsDocumentation');
        $this->metrics = array('entry_nb_visits', 'entry_bounce_count');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->order   = 6;
        $this->actionToLoadSubTables = $this->action;
        $this->subcategoryId = 'Actions_SubmenuPagesEntry';
        $this->hasGoalMetrics = true;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig($factory->createWidget()->setName('Actions_WidgetEntryPageTitles'));
    }

    public function getProcessedMetrics()
    {
        $result = parent::getProcessedMetrics();

        // these metrics are not displayed in the API.getProcessedReport version of this report,
        // so they are removed here.
        unset($result['avg_time_on_page']);
        unset($result['exit_rate']);

        return $result;
    }

    protected function getMetricsDocumentation()
    {
        $metrics = parent::getMetricsDocumentation();
        $metrics['bounce_rate'] = Matomo::translate('General_ColumnPageBounceRateDocumentation');

        // remove these metrics from API.getProcessedReport version of this report
        unset($metrics['avg_time_on_page']);
        unset($metrics['exit_rate']);

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display = array('label', 'entry_nb_visits', 'entry_bounce_count', 'bounce_rate');
        $view->config->title = $this->name;

        $view->requestConfig->filter_sort_column = 'entry_nb_visits';

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);

        $view->config->show_goals = true;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getPageTitles'),
            ReportsProvider::factory('Actions', 'getEntryPageUrls'),
        );
    }
}
