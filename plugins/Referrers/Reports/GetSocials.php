<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Pie;
use Matomo\Plugins\Referrers\Columns\SocialNetwork;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetSocials extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new SocialNetwork();
        $this->name          = Matomo::translate('Referrers_Socials');
        $this->documentation = Matomo::translate('Referrers_SocialsReportDocumentation', '<br />');
        $this->actionToLoadSubTables = 'getUrlsForSocial';
        $this->hasGoalMetrics = true;
        $this->order = 11;

        $this->subcategoryId = 'Referrers_Socials';
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widget = $factory->createWidget()->setName('Referrers_Socials');
        $widgetsList->addWidgetConfig($widget);
    }

    public function getDefaultTypeViewDataTable()
    {
        return Pie::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_pivot_by_subtable = false;
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 10;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_subtable_when_show_goals = true;
        }
    }
}
