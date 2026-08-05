<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns\Metrics;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;

/**
 * The percentage of visits that leave the site without visiting another page. Calculated
 * as:
 *
 *     bounce_count / nb_visits
 *
 * bounce_count & nb_visits are calculated by an Archiver.
 */
class BounceRate extends ProcessedMetric
{
    public function getName()
    {
        return 'bounce_rate';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_ColumnBounceRate');
    }

    public function getDependentMetrics()
    {
        return array('bounce_count', 'nb_visits');
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function compute(Row $row)
    {
        $bounceCount = $this->getMetric($row, 'bounce_count');
        $visits = $this->getMetric($row, 'nb_visits');

        return Matomo::getQuotientSafe($bounceCount, $visits, $precision = 2);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}
