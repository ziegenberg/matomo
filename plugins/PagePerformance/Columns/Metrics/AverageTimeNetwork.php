<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PagePerformance\Columns\Metrics;

use Matomo\Matomo;

/**
 * The average amount of time needed to connect to the server. Calculated as
 *
 *     sum_time_network / nb_hits_with_time_network
 *
 * The above metrics are calculated during archiving. This metric is calculated before
 * serving a report.
 */
class AverageTimeNetwork extends AveragePerformanceMetric
{
    public const ID = 'time_network';

    public function getTranslatedName()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeNetwork');
    }

    public function getDocumentation()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeNetworkDocumentation');
    }
}
