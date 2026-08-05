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
 * The average order revenue for a specific goal. Calculated as:
 *
 *     goals' revenue / goal's nb_conversions
 */
class AverageOrderRevenue extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'avg_order_revenue', false);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_AverageOrderValue');
    }

    public function getDocumentation()
    {
        return Matomo::translate('Goals_ColumnAverageOrderRevenueDocumentation', $this->getGoalNameForDocs());
    }

    public function getDependentMetrics()
    {
        return array('goals');
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);

        $goalRevenue = $this->getMetric($goalMetrics, 'revenue', $mappingFromNameToIdGoal);
        $conversions = $this->getMetric($goalMetrics, 'nb_conversions', $mappingFromNameToIdGoal);

        return Matomo::getQuotientSafe($goalRevenue, $conversions, GoalManager::REVENUE_PRECISION);
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
