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
 * The average amount of time the browser needs to load media any Javascript listening for the DOMContentLoaded event.
 * Calculated as
 *
 *     sum_time_dom_completion / nb_hits_with_time_dom_completion
 *
 * The above metrics are calculated during archiving. This metric is calculated before
 * serving a report.
 */
class AverageTimeDomCompletion extends AveragePerformanceMetric
{
    public const ID = 'time_dom_completion';

    public function getTranslatedName()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeDomCompletion');
    }

    public function getDocumentation()
    {
        return Matomo::translate('PagePerformance_ColumnAverageTimeDomCompletionDocumentation');
    }
}
