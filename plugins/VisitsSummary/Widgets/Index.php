<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitsSummary\Widgets;

use Matomo\Plugin\ReportsProvider;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

class Index extends \Matomo\Widget\WidgetContainerConfig
{
    protected $categoryId = 'General_Visitors';
    protected $name = 'VisitsSummary_WidgetOverviewGraph';
    protected $id = 'VisitOverviewWithGraph';
    protected $isWidgetizable = true;

    public function isEnabled()
    {
        return ReportsProvider::factory('VisitsSummary', 'get')->isEnabled();
    }

    public function getWidgetConfigs()
    {
        $report  = ReportsProvider::factory('VisitsSummary', 'get');

        $factory = new ReportWidgetFactory($report);
        $widgets = array();

        $list = new WidgetsList();
        $report->configureWidgets($list, $factory);

        foreach ($list->getWidgetConfigs() as $config) {
            $config->setIsNotWidgetizable();
            $widgets[] = $config;
        }

        return $widgets;
    }
}
