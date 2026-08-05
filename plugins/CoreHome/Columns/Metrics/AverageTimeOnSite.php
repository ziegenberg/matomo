<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns\Metrics;

use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Columns\Dimension;

/**
 * The average number of seconds spent on the site per visit. Calculated as:
 *
 *     sum_visit_length / nb_visits
 *
 * sum_visit_length & nb_visits are calculated during archiving.
 *
 * @api
 */
class AverageTimeOnSite extends ProcessedMetric
{
    public function getName()
    {
        return 'avg_time_on_site';
    }

    public function compute(Row $row)
    {
        $sumVisitLength = $this->getMetric($row, 'sum_visit_length');
        $nbVisits = $this->getMetric($row, 'nb_visits');

        return Matomo::getQuotientSafe($sumVisitLength, $nbVisits, $precision = 0);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyTimeFromSeconds($value, true);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_ColumnAvgTimeOnSite');
    }

    public function getDependentMetrics()
    {
        return array('sum_visit_length', 'nb_visits');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_DURATION_S;
    }
}
