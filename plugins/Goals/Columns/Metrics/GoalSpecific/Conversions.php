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
use Matomo\Matomo;
use Matomo\Plugins\Goals\Columns\Metrics\GoalSpecificProcessedMetric;
use Matomo\Plugins\Goals\Goals;

/**
 * The conversions for a specific goal. Returns the conversions for a single goal which
 * is then treated as a new column.
 */
class Conversions extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'nb_conversions', false);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('Goals_Conversions', $this->getGoalNameForDocs());
    }

    public function getDocumentation()
    {
        if ($this->idGoal < 1) {
            return Matomo::translate('Goals_ColumnEcommerceOrdersDocumentation');
        }

        return Matomo::translate('Goals_ColumnConversionsDocumentation', $this->getGoalNameForDocs());
    }
    public function getDependentMetrics()
    {
        return array('goals');
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);
        return (int) $this->getMetric($goalMetrics, 'nb_conversions', $mappingFromNameToIdGoal);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
