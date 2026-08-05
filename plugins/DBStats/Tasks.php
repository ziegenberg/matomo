<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DBStats;

use Matomo\Date;
use Matomo\Option;

class Tasks extends \Matomo\Plugin\Tasks
{
    public function schedule()
    {
        $this->weekly('cacheDataByArchiveNameReports', null, self::LOWEST_PRIORITY);
    }

    /**
     * Caches the intermediate DataTables used in the getIndividualReportsSummary and
     * getIndividualMetricsSummary reports in the option table.
     */
    public function cacheDataByArchiveNameReports()
    {
        $api = API::getInstance();
        $api->getIndividualReportsSummary(true);
        $api->getIndividualMetricsSummary(true);

        $now = Date::now()->getLocalized(Date::DATE_FORMAT_SHORT);
        Option::set(DBStats::TIME_OF_LAST_TASK_RUN_OPTION, $now);
    }
}
