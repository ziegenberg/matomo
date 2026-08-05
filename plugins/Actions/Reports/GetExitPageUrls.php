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
use Matomo\API\Request;
use Matomo\Plugins\Actions\Columns\ExitPageUrl;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;

class GetExitPageUrls extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new ExitPageUrl();
        $this->name          = Matomo::translate('Actions_SubmenuPagesExit');
        $this->documentation = Matomo::translate('Actions_ExitPagesReportDocumentation', '<br />')
                             . '<br />' . Matomo::translate('General_UsePlusMinusIconsDocumentation');

        $this->metrics = array('exit_nb_visits', 'nb_visits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->actionToLoadSubTables = $this->action;

        $this->order = 4;

        $this->subcategoryId = 'Actions_SubmenuPagesExit';
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

        unset($metrics['bounce_rate']);
        unset($metrics['avg_time_on_page']);

        return $metrics;
    }

    protected function getMetricsDocumentation()
    {
        $metrics = parent::getMetricsDocumentation();
        $metrics['nb_visits'] = Matomo::translate('General_ColumnUniquePageviewsDocumentation');

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->self_url = Request::getCurrentUrlWithoutGenericFilters(array(
            'module' => 'Actions',
            'action' => 'getExitPageUrls',
        ));

        $view->config->title = $this->name;

        $view->config->columns_to_display        = array('label', 'exit_nb_visits', 'nb_visits', 'exit_rate');
        $view->requestConfig->filter_sort_column = 'exit_nb_visits';
        $view->requestConfig->filter_sort_order  = 'desc';

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getExitPageTitles'),
        );
    }
}
