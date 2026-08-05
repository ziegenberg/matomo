<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DBStats\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\Graph;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Pie;

/**
 * Shows a datatable that displays how much space the tracker tables, numeric
 * archive tables, report tables and other tables take up in the MySQL database.
 */
class GetDatabaseUsageSummary extends Base
{
    protected function init()
    {
        $this->name = Matomo::translate('General_Overview');
    }

    public function getDefaultTypeViewDataTable()
    {
        return Pie::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $this->addBaseDisplayProperties($view);
        $this->addPresentationFilters($view, $addTotalSizeColumn = true, $addPercentColumn = true);

        $view->config->show_offset_information = false;
        $view->config->show_pagination_control = false;

        if ($view->isViewDataTableId(Graph::ID)) {
            $view->config->show_all_ticks = true;
        }

        // translate the labels themselves
        $valueToTranslationStr = array(
            'tracker_data' => 'DBStats_TrackerTables',
            'report_data'  => 'DBStats_ReportTables',
            'metric_data'  => 'DBStats_MetricTables',
            'other_data'   => 'DBStats_OtherTables',
        );

        $translateSummaryLabel = function ($value) use ($valueToTranslationStr) {
            return isset($valueToTranslationStr[$value])
                ? Matomo::translate($valueToTranslationStr[$value])
                : $value;
        };

        $view->config->filters[] = array('ColumnCallbackReplace', array('label', $translateSummaryLabel), $isPriority = true);
    }
}
