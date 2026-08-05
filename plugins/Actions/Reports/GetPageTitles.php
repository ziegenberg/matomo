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
use Matomo\Plugin\ViewDataTable;
use Matomo\API\Request;
use Matomo\Plugins\Actions\Columns\PageTitle;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;

class GetPageTitles extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new PageTitle();
        $this->name          = Matomo::translate('Actions_SubmenuPageTitles');
        $this->documentation = Matomo::translate(
            'Actions_PageTitlesReportDocumentation',
            ['<br />', htmlentities('<title>', ENT_COMPAT | ENT_HTML401, 'UTF-8')]
        );

        $this->order   = 5;
        $this->metrics = array('nb_hits', 'nb_visits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );

        $this->actionToLoadSubTables = $this->action;
        $this->subcategoryId = 'Actions_SubmenuPageTitles';
        $this->hasGoalMetrics = true;
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
        $metrics['nb_visits']   = Matomo::translate('General_ColumnUniquePageviewsDocumentation');
        $metrics['bounce_rate'] = Matomo::translate('General_ColumnPageBounceRateDocumentation');

        return $metrics;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->self_url = Request::getCurrentUrlWithoutGenericFilters(array(
            'module' => $this->module,
            'action' => 'getPageTitles',
        ));

        $view->config->title = $this->name;

        $view->config->columns_to_display = array('label', 'nb_hits', 'nb_visits', 'bounce_rate',
                                                  'avg_time_on_page', 'exit_rate');

        if (version_compare(DbHelper::getInstallVersion(), '4.0.0-b1', '<')) {
            $view->config->columns_to_display[] = 'avg_time_generation';
        }

        $this->addPageDisplayProperties($view);
        $this->addBaseDisplayProperties($view);

        $view->config->show_goals = true;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getEntryPageTitles'),
            ReportsProvider::factory('Actions', 'getExitPageTitles'),
        );
    }
}
