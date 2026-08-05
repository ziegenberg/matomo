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
use Matomo\Plugins\Actions\Columns\KeywordwithNoSearchResult;
use Matomo\Plugins\Actions\Columns\Metrics\AveragePageGenerationTime;
use Matomo\Plugins\Actions\Columns\Metrics\AverageTimeOnPage;
use Matomo\Plugins\Actions\Columns\Metrics\BounceRate;
use Matomo\Plugins\Actions\Columns\Metrics\ExitRate;

class GetSiteSearchNoResultKeywords extends SiteSearchBase
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new KeywordwithNoSearchResult();
        $this->name          = Matomo::translate('Actions_WidgetSearchNoResultKeywords');
        $this->documentation = Matomo::translate('Actions_SiteSearchIntro') . '<br /><br />' . Matomo::translate('Actions_SiteSearchKeywordsNoResultDocumentation');
        $this->metrics       = array('nb_visits');
        $this->processedMetrics = array(
            new AverageTimeOnPage(),
            new BounceRate(),
            new ExitRate(),
            new AveragePageGenerationTime(),
        );
        $this->order = 18;

        $this->subcategoryId = 'Actions_SubmenuSitesearch';
    }

    public function getMetrics()
    {
        return array(
            'nb_visits' => Matomo::translate('Actions_ColumnSearches'),
        );
    }

    public function getProcessedMetrics()
    {
        return array(
            'exit_rate' => Matomo::translate('Actions_ColumnSearchExits'),
        );
    }

    protected function getMetricsDocumentation()
    {
        return array(
            'nb_visits' => Matomo::translate('Actions_ColumnSearchesDocumentation'),
            'exit_rate' => Matomo::translate('Actions_ColumnSearchExitsDocumentation'),
        );
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display = array('label', 'nb_visits', 'exit_rate');

        $this->addSiteSearchDisplayProperties($view);
    }
}
