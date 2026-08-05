<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Matomo\Plugins\Referrers\Columns\Website;

class GetWebsites extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Website();
        $this->name          = Matomo::translate('CorePluginsAdmin_Websites');
        $this->documentation = Matomo::translate('Referrers_WebsitesReportDocumentation', '<br />');
        $this->recursiveLabelSeparator = '/';
        $this->actionToLoadSubTables = 'getUrlsFromWebsiteId';
        $this->hasGoalMetrics = true;
        $this->order = 5;

        $this->subcategoryId = 'Referrers_SubmenuWebsitesOnly';
    }

    public function getDefaultTypeViewDataTable()
    {
        if (Common::getRequestVar('widget', 0, 'int')) {
            return parent::getDefaultTypeViewDataTable();
        }
        return HtmlTable\AllColumns::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 25;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_subtable_when_show_goals = true;
        }

        $view->config->show_pivot_by_subtable = false;
    }
}
