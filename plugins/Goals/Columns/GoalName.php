<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\DataTable;
use Matomo\DataTable\Filter\ColumnDelete;
use Matomo\Plugin\Dimension\ConversionDimension;
use Matomo\Columns\Join;
use Matomo\Plugins\Goals\API;

class GoalName extends ConversionDimension
{
    protected $columnName = 'idgoal';
    protected $type = self::TYPE_TEXT;
    protected $category = 'General_Visitors'; // todo move into goal category?
    protected $nameSingular = 'General_VisitConvertedGoalName';
    protected $segmentName = 'visitConvertedGoalName';
    protected $acceptValues = 'myGoal, myOtherGoal, etc.';
    protected $suggestedValuesCallback = [self::class, 'getGoalNameForSuggestedValues'];

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // do not create any metrics for this dimension, they don't really make much sense and are rather confusing
    }

    public function getDbColumnJoin()
    {
        return new Join\GoalNameJoin();
    }

    public static function getGoalNameForSuggestedValues($idSite, $maxSuggestionsToReturn, DataTable $table)
    {
        $goals = API::getInstance()->getGoals($idSite);

        $convertedGoals = $table->getColumnsStartingWith('visitConvertedGoalId' . ColumnDelete::APPEND_TO_COLUMN_NAME_TO_KEEP);
        $convertedGoals = array_map(function ($idGoal) use ($goals) {
            return $goals[$idGoal]['name'] ?? null;
        }, $convertedGoals);
        $convertedGoals = array_filter($convertedGoals);
        return $convertedGoals;
    }
}
