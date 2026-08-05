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
 * The number of ecommerce order items for conversions of a goal. Returns the 'items'
 * goal specific metric.
 */
class ItemsCount extends GoalSpecificProcessedMetric
{
    public function getName()
    {
        return Goals::makeGoalColumn($this->idGoal, 'items', false);
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_PurchasedProducts');
    }

    public function getDocumentation()
    {
        return Matomo::translate('Goals_ColumnPurchasedProductsDocumentation', $this->getGoalNameForDocs());
    }

    public function getDependentMetrics()
    {
        return array('goals');
    }

    public function compute(Row $row)
    {
        $mappingFromNameToIdGoal = Metrics::getMappingFromNameToIdGoal();

        $goalMetrics = $this->getGoalMetrics($row);
        return (int) $this->getMetric($goalMetrics, 'items', $mappingFromNameToIdGoal);
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
