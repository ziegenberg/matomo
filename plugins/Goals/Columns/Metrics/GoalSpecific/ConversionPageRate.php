<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns\Metrics\GoalSpecific;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Metrics;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugins\Goals\Columns\Metrics\GoalSpecificProcessedMetric;
use Matomo\Plugins\Goals\Goals;

/**
 * The page conversion rate for a specific goal. Calculated as:
 *
 *     goal's nb_conversions / sum_daily_nb_uniq_visitors
 *
 * The goal's nb_conversions is calculated by the Goal archiver and nb_visits
 * by the core archiving process.
 */
class ConversionPageRate extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'nb_conversions_page_rate', false);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('Goals_ConversionRatePageViewedBefore', $this->getGoalName());
    }

    public function getDocumentation()
    {
        return Matomo::translate('Goals_ColumnConversionRatePageViewedBeforeDocumentation', $this->getGoalNameForDocs());
    }

    public function getDependentMetrics()
    {
        return ['goals'];
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();
        $goalMetrics = $this->getGoalMetrics($row);

        return $this->getMetric($goalMetrics, 'nb_conversions_page_rate', $mappingFromNameToIdGoal);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}
