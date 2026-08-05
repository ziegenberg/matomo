<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicePlugins\Reports;

use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\Graph;

abstract class Base extends \Matomo\Plugin\Report
{
    protected function init()
    {
        $this->categoryId = 'General_Visitors';
    }

    protected function getBasicDevicePluginsDisplayProperties(ViewDataTable $view)
    {
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 5;

        if ($view->isViewDataTableId(Graph::ID)) {
            $view->config->max_graph_elements = 5;
        }
    }
}
