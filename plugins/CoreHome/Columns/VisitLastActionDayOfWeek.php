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

require_once PIWIK_INCLUDE_PATH . '/plugins/VisitTime/functions.php';

class VisitLastActionDayOfWeek extends VisitDimension
{
    protected $columnName = 'visit_last_action_time';
    protected $type = self::TYPE_DATETIME;
    protected $segmentName = 'visitEndServerDayOfWeek';
    protected $nameSingular = 'VisitTime_ColumnVisitEndUTCDayOfWeek';
    protected $sqlSegment = 'DAYOFWEEK(log_visit.visit_last_action_time)';
    protected $acceptValues = '1, 2, 3, 4, 5, 6, 7';

    public function __construct()
    {
        $this->suggestedValuesCallback = function ($idSite, $maxValuesToReturn) {
            return range(1, min(7, $maxValuesToReturn));
        };
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // no metrics for this dimension
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        return \Matomo\Plugins\VisitTime\translateDayOfWeek($value);
    }
}
