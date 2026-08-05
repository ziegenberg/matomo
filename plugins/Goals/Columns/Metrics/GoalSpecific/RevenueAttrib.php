<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns\Metrics\GoalSpecific;

use Matomo\Archive\DataTableFactory;
use Matomo\Columns\Dimension;
use Matomo\DataTable;
use Matomo\DataTable\Row;
use Matomo\Metrics;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugins\Goals\Columns\Metrics\GoalSpecificProcessedMetric;
use Matomo\Plugins\Goals\Goals;

/**
 * Attributed Revenue for a specific goal.
 */
class RevenueAttrib extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'revenue_attrib', false);
    }

    public function getTranslatedName()
    {
        return Matomo::translate(Matomo::translate('Goals_NRevenue'), $this->getGoalName());
    }

    public function getDocumentation()
    {
        return Matomo::translate('Goals_ColumnRevenueAttributedDocumentation', $this->getGoalNameForDocs());
    }

    public function getDependentMetrics()
    {
        return ['goals'];
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);
        return (float) $this->getMetric($goalMetrics, 'revenue_attrib', $mappingFromNameToIdGoal);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyMoney($value, $this->idSite);
    }

    public function beforeFormat($report, DataTable $table)
    {
        $this->idSite = DataTableFactory::getSiteIdFromMetadata($table);
        return !empty($this->idSite); // skip formatting if there is no site to get currency info from
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_MONEY;
    }
}
