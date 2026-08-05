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
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class VisitsCount extends VisitDimension
{
    protected $columnName = 'visitor_count_visits';
    protected $columnType = 'INT(11) UNSIGNED NOT NULL DEFAULT 0';
    protected $segmentName = 'visitCount';
    protected $nameSingular = 'General_NumberOfVisits';
    protected $type = self::TYPE_NUMBER;

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // no metrics for this dimension, it would be rather confusing I think
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $previousVisitCount = $visitor->getPreviousVisitColumn($this->columnName);
        if ($previousVisitCount === false || $previousVisitCount === null || $previousVisitCount === '') {
            return 1;
        }
        $result = $previousVisitCount + 1;
        return $result;
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onAnyGoalConversion(Request $request, Visitor $visitor, $action)
    {
        return $visitor->getVisitorColumn($this->columnName);
    }
}
