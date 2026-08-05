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

class VisitFirstActionMinute extends VisitDimension
{
    protected $columnName = 'visit_first_action_time';
    protected $type = self::TYPE_DATETIME;

    protected $sqlSegment = 'MINUTE(log_visit.visit_first_action_time)';
    protected $segmentName = 'visitStartServerMinute';
    protected $acceptValues = '0, 1, 2, 3, ..., 56, 57, 58, 59';
    protected $nameSingular = 'VisitTime_ColumnVisitStartUTCMinute';

    public function __construct()
    {
        $this->suggestedValuesCallback = function ($idSite, $maxValuesToReturn) {
            return range(0, min(59, $maxValuesToReturn));
        };
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // no metrics to be generated
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        return $value;
    }
}
