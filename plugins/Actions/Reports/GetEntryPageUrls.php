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
use Matomo\Plugins\Actions\Columns\EntryPageUrl;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;

class GetEntryPageUrls extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new EntryPageUrl();
        $this->name          = Matomo::translate('Actions_SubmenuPagesEntry');
        $this->documentation = Matomo::translate('Actions_EntryPagesReportDocumentation', '<br />')
                             . '<br />' . Matomo::translate('General_UsePlusMinusIconsDocumentation');

        $this->metrics = array('entry_nb_visits', 'entry_bounce_count');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->order   = 3;

        $this->actionToLoadSubTables = $this->action;
        $this->subcategoryId = 'Actions_SubmenuPagesEntry';
        $this->hasGoalMetrics = true;
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

        unset($metrics['avg_time_on_page']);
        unset($metrics['exit_rate']);

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->title = $this->name;
        $view->config->columns_to_display = array('label', 'entry_nb_visits', 'entry_bounce_count', 'bounce_rate');
        $view->requestConfig->filter_sort_column = 'entry_nb_visits';
        $view->requestConfig->filter_sort_order  = 'desc';

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);

        $view->config->show_goals = true;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getEntryPageTitles'),
        );
    }
}
