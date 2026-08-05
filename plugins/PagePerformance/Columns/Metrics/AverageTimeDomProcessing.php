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
 * The average amount of time the browser spends until user can start interacting with the page. Calculated as
 *
 *     sum_time_dom_processing / nb_hits_with_time_dom_processing
 *
 * The above metrics are calculated during archiving. This metric is calculated before
 * serving a report.
 */
class AverageTimeDomProcessing extends AveragePerformanceMetric
{
    public const ID = 'time_dom_processing';

    public function getTranslatedName()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeDomProcessing');
    }

    public function getDocumentation()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeDomProcessingDocumentation');
    }
}
