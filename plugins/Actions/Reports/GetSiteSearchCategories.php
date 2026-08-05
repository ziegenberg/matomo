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
use Matomo\Plugins\Actions\Columns\SearchCategory;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;

class GetSiteSearchCategories extends SiteSearchBase
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new SearchCategory();
        $this->name          = Matomo::translate('Actions_WidgetSearchCategories');
        $this->documentation = Matomo::translate('Actions_SiteSearchCategories1') . '<br/>' . Matomo::translate('Actions_SiteSearchCategories2');
        $this->metrics       = array('nb_visits', 'nb_pages_per_search', 'exit_rate');
        $this->order = 20;

        $this->subcategoryId = 'Actions_SubmenuSitesearch';
    }

    protected function isEnabledForIdSites($idSites, $idSite)
    {
        return parent::isEnabledForIdSites($idSites, $idSite);
    }

    public function getMetrics()
    {
        return array(
            'nb_visits'           => Matomo::translate('Actions_ColumnSearches'),
            'nb_pages_per_search' => Matomo::translate('Actions_ColumnPagesPerSearch'),
            'exit_rate'           => Matomo::translate('Actions_ColumnSearchExits'),
        );
    }

    protected function getMetricsDocumentation()
    {
        return array(
            'nb_visits'           => Matomo::translate('Actions_ColumnSearchesDocumentation'),
            'nb_pages_per_search' => Matomo::translate('Actions_ColumnPagesPerSearchDocumentation'),
            'exit_rate'           => Matomo::translate('Actions_ColumnSearchExitsDocumentation'),
        );
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->columns_to_display     = array('label', 'nb_visits', 'nb_pages_per_search');
        $view->config->show_table_all_columns = false;
        $view->config->show_bar_chart         = false;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_row_evolution = false;
        }
    }
}
