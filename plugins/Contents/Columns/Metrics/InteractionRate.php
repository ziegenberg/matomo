<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Contents\Columns\Metrics;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;

/**
 * The content interaction rate. Calculated as:
 *
 *     nb_interactions / nb_impressions
 *
 * nb_interactions & nb_impressions are calculated by the Contents archiver.
 */
class InteractionRate extends ProcessedMetric
{
    public function getName()
    {
        return 'interaction_rate';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('Contents_InteractionRate');
    }

    public function getDocumentation()
    {
        return Matomo::translate('Contents_InteractionRateMetricDocumentation');
    }

    public function compute(Row $row)
    {
        $interactions = $this->getMetric($row, 'nb_interactions');
        $impressions = $this->getMetric($row, 'nb_impressions');

        return Matomo::getQuotientSafe($interactions, $impressions, $precision = 4);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function getDependentMetrics()
    {
        return array('nb_interactions', 'nb_impressions');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}
