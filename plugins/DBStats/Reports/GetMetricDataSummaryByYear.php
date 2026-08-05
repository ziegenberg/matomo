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
use Matomo\Plugin\ReportsProvider;

/**
 * Shows a datatable that displays the amount of space each numeric archive table
 * takes up in the MySQL database, for each year of numeric data.
 */
class GetMetricDataSummaryByYear extends Base
{
    protected function init()
    {
        $this->name = Matomo::translate('DBStats_MetricDataByYear');
    }

    public function configureView(ViewDataTable $view)
    {
        $this->addBaseDisplayProperties($view);
        $this->addPresentationFilters($view);

        $view->config->title = $this->name;
        $view->config->addTranslation('label', Matomo::translate('Intl_PeriodYear'));
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('DBStats', 'getMetricDataSummary'),
        );
    }
}
