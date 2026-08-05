<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Metrics\Formatter;

class VisitLastActionDayOfMonth extends VisitDimension
{
    protected $columnName = 'visit_last_action_time';
    protected $type = self::TYPE_DATETIME;
    protected $segmentName = 'visitEndServerDayOfMonth';
    protected $nameSingular = 'VisitTime_ColumnVisitEndUTCDayOfMonth';
    protected $sqlSegment = 'DAYOFMONTH(log_visit.visit_last_action_time)';
    protected $acceptValues = '0, 1, 2, 3, ..., 29, 30, 31';

    public function __construct()
    {
        $this->suggestedValuesCallback = function ($idSite, $maxValuesToReturn) {
            return range(0, min(31, $maxValuesToReturn));
        };
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // no metrics for this dimension
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        return $value;
    }
}
