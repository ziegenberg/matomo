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
use Matomo\Plugins\Referrers\Columns\Referrer;
use Matomo\Plugins\Referrers\Referrers;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetAll extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Referrer();
        $this->name          = Matomo::translate('Referrers_WidgetGetAll');
        $this->documentation = Matomo::translate('Referrers_AllReferrersReportDocumentation', '<br />');
        $this->order = 2;

        $this->subcategoryId = 'Referrers_WidgetGetAll';
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig(
            $factory->createWidget()->setName('Referrers_Referrers')
        );
    }

    public function getDefaultTypeViewDataTable()
    {
        return HtmlTable\AllColumns::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $referrers = new Referrers();
        $setGetAllHtmlPrefix = array($referrers, 'setGetAllHtmlPrefix');

        $view->config->show_exclude_low_population = false;
        $view->config->show_goals = true;

        $view->requestConfig->filter_limit = 20;

        if ($view->isViewDataTableId(HtmlTable::ID)) {
            $view->config->disable_row_evolution = true;
        }

        $view->config->filters[] = array('MetadataCallbackAddMetadata', array('referer_type', 'html_label_prefix', $setGetAllHtmlPrefix));
    }
}
