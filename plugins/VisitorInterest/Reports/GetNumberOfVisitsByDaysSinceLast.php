<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\VisitorInterest\Columns\VisitorDaysSinceLast;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetNumberOfVisitsByDaysSinceLast extends Base
{
    protected $defaultSortColumn = '';

    protected function init()
    {
        parent::init();
        $this->dimension     = new VisitorDaysSinceLast();
        $this->name          = Matomo::translate('VisitorInterest_VisitsByDaysSinceLast');
        $this->documentation = Matomo::translate('VisitorInterest_WidgetVisitsByDaysSinceLastDocumentation');
        $this->metrics       = array('nb_visits');
        $this->processedMetrics = [];
        $this->constantRowsCount = true;
        $this->order = 30;

        $this->subcategoryId = 'VisitorInterest_Engagement';
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widget = $factory->createWidget()->setName('VisitorInterest_WidgetVisitsByDaysSinceLast');
        $widgetsList->addWidgetConfig($widget);
    }

    public function configureView(ViewDataTable $view)
    {
        $view->requestConfig->filter_sort_column = 'label';
        $view->requestConfig->filter_sort_order  = 'asc';
        $view->requestConfig->filter_limit = 15;

        $view->config->show_search = false;
        $view->config->enable_sort = false;
        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;
        $view->config->show_all_views_icons    = false;
        $view->config->show_table_all_columns  = false;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', Matomo::translate('General_DaysSinceLastVisit'));

        if (!$view->isViewDataTableId(Evolution::ID)) {
            $view->config->show_limit_control = false;
        }
    }
}
