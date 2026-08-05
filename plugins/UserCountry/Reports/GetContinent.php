<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\UserCountry\Columns\Continent;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class GetContinent extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension      = new Continent();
        $this->name           = Matomo::translate('UserCountry_Continent');
        $this->documentation  = Matomo::translate('UserCountry_getContinentDocumentation');
        $this->order = 6;
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addWidgetConfig($factory->createContainerWidget('Continent'));

        $widgetsList->addToContainerWidget('Continent', $factory->createWidget());

        $widget = $factory->createWidget()->setAction('getDistinctCountries')->setName('');
        $widgetsList->addToContainerWidget('Continent', $widget);
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->show_exclude_low_population = false;
        $view->config->show_search = false;
        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;
        $view->config->documentation = $this->documentation;

        if (!$view->isViewDataTableId(Evolution::ID)) {
            $view->config->show_limit_control = false;
        }
    }
}
