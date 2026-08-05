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
 * The average amount of time it takes to transfer a page. Calculated as
 *
 *     sum_time_transfer / nb_hits_with_time_transfer
 *
 * The above metrics are calculated during archiving. This metric is calculated before
 * serving a report.
 */
class AverageTimeTransfer extends AveragePerformanceMetric
{
    public const ID = 'time_transfer';

    public function getTranslatedName()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeTransfer');
    }

    public function getDocumentation()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeTransferDocumentation');
    }
}
