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
use Matomo\Tracker\GoalManager;

/**
 * Revenue per entry for a specific goal. Calculated as:
 *
 *     goal's revenue / entry_nb_visits
 *
 * Goal revenue and entry_nb_visits are calculated by the Goals archiver.
 */
class RevenuePerEntry extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'revenue_per_entry', false);
    }

    public function getTranslatedName()
    {
        return $this->getGoalName() . ' ' . Matomo::translate('General_ColumnValuePerEntry');
    }

    public function getDocumentation()
    {
        if ($this->idGoal === Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER) {
            return Matomo::translate('Goals_ColumnAverageOrderRevenueDocumentation', $this->getGoalNameForDocs());
        }

        return Matomo::translate('Goals_ColumnRevenuePerEntryDocumentation', Matomo::translate('Goals_EcommerceAndGoalsMenu'));
    }

    public function getDependentMetrics()
    {
        return ['goals', 'entry_nb_visits'];
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);

        $nbEntrances = $this->getMetric($row, 'entry_nb_visits');
        $conversions = $this->getMetric($goalMetrics, 'nb_conversions', $mappingFromNameToIdGoal);

        $goalRevenue = (float) $this->getMetric($goalMetrics, 'revenue', $mappingFromNameToIdGoal);

        return Matomo::getQuotientSafe($goalRevenue, $nbEntrances == 0 ? $conversions : $nbEntrances, GoalManager::REVENUE_PRECISION);
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
