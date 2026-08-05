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
use Matomo\Plugin\Dimension\ConversionDimension;

class IdGoal extends ConversionDimension
{
    protected $columnName = 'idgoal';
    protected $type = self::TYPE_TEXT;
    protected $category = 'General_Visitors'; // todo move into goal category?
    protected $nameSingular = 'General_VisitConvertedGoalId';
    protected $segmentName = 'visitConvertedGoalId';
    protected $acceptValues = '1, 2, 3, etc.';

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // do not create any metrics for this dimension, they don't really make much sense and are rather confusing
    }
}
