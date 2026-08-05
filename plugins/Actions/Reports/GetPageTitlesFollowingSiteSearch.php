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
use Matomo\Plugins\Actions\Columns\DestinationPage;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;
use Matomo\Plugin\ReportsProvider;

class GetPageTitlesFollowingSiteSearch extends SiteSearchBase
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new DestinationPage();
        $this->name          = Matomo::translate('Actions_WidgetPageTitlesFollowingSearch');
        $this->documentation = Matomo::translate('Actions_SiteSearchFollowingPagesDoc') . '<br/>' . Matomo::translate('General_UsePlusMinusIconsDocumentation');
        $this->metrics       = array('nb_hits_following_search', 'nb_hits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->order = 19;

        $this->subcategoryId = 'Actions_SubmenuSitesearch';
    }

    public function configureView(ViewDataTable $view)
    {
        $title = Matomo::translate('Actions_WidgetPageUrlsFollowingSearch');

        $this->configureViewForUrlAndTitle($view, $title);
    }

    public function getMetrics()
    {
        return array(
            'nb_hits_following_search' => Matomo::translate('General_ColumnViewedAfterSearch'),
            'nb_hits'                  => Matomo::translate('General_ColumnPageviews'),
        );
    }

    public function getProcessedMetrics()
    {
        return array();
    }

    protected function getMetricsDocumentation()
    {
        return array(
            'nb_hits_following_search' => Matomo::translate('General_ColumnViewedAfterSearchDocumentation'),
            'nb_hits'                  => Matomo::translate('General_ColumnPageviewsDocumentation'),
        );
    }

    protected function configureViewForUrlAndTitle(ViewDataTable $view, $title)
    {
        $view->config->title = $title;
        $view->config->columns_to_display          = array('label', 'nb_hits_following_search', 'nb_hits');
        $view->config->show_exclude_low_population = false;
        $view->requestConfig->filter_sort_column = 'nb_hits_following_search';
        $view->requestConfig->filter_sort_order  = 'desc';

        $this->addExcludeLowPopDisplayProperties($view);
        $this->addBaseDisplayProperties($view);
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Actions', 'getPageUrlsFollowingSiteSearch'),
        );
    }
}
