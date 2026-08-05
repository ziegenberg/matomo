<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns\Metrics;

use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Columns\Dimension;

/**
 * The average amount of time spent on a page. Calculated as:
 *
 *     sum_time_spent / nb_hits
 *
 * sum_time_spent and nb_hits are calculated by Archiver classes.
 */
class AverageTimeOnPage extends ProcessedMetric
{
    public function getName()
    {
        return 'avg_time_on_page';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_ColumnAverageTimeOnPage');
    }

    public function compute(Row $row)
    {
        $sumTimeSpent = $this->getMetric($row, 'sum_time_spent');
        $visits = $this->getMetric($row, 'nb_hits');

        return Matomo::getQuotientSafe($sumTimeSpent, $visits, $precision = 0);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyTimeFromSeconds($value, $timeAsSentence = false);
    }

    public function getDependentMetrics()
    {
        return array('sum_time_spent', 'nb_hits');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_DURATION_S;
    }
}
