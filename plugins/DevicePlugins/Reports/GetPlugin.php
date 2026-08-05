<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicePlugins\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\DevicePlugins\Columns\Plugin;
use Matomo\Plugins\DevicePlugins\DevicePlugins;

class GetPlugin extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Plugin();
        $this->name          = Matomo::translate('DevicePlugins_WidgetPlugins');
        $this->documentation = Matomo::translate('DevicePlugins_WidgetPluginsDocumentation', '<br />');
        $this->metrics       = array('nb_visits');
        $this->constantRowsCount = true;
        $this->processedMetrics = array('nb_visits_percentage');
        $this->order = 13;

        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function getMetricsDocumentation()
    {
        $documentation = parent::getMetricsDocumentation();

        $documentation['nb_visits_percentage'] = Matomo::translate('DevicePlugins_ColumnPercentageVisitsDocumentation');

        return $documentation;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $this->getBasicDevicePluginsDisplayProperties($view);

        $view->config->addTranslations(array(
            'nb_visits_percentage' =>
            str_replace(' ', '&nbsp;', Matomo::translate('General_ColumnPercentageVisits')),
        ));

        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;
        $view->config->show_all_views_icons    = false;
        $view->config->show_table_all_columns  = false;
        $view->config->show_totals_row         = false;
        $view->config->columns_to_display  = array('label', 'nb_visits_percentage', 'nb_visits');
        $view->config->show_footer_message = Matomo::translate('DevicePlugins_PluginDetectionDoesNotWorkInIE');

        if (!$view->isViewDataTableId(Evolution::ID)) {
            $view->config->show_limit_control = false;
        }

        $view->requestConfig->filter_sort_column = 'nb_visits_percentage';
        $view->requestConfig->filter_sort_order  = 'desc';
        $view->requestConfig->filter_limit       = count(DevicePlugins::getAllPluginColumns());
        $view->requestConfig->totals             = 0;
    }
}
