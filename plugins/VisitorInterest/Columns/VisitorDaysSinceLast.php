<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest\Columns;

use Matomo\Plugin\Dimension\VisitDimension;

class VisitorDaysSinceLast extends VisitDimension
{
    protected $category = 'General_Visitors';
    protected $type = self::TYPE_NUMBER;
    protected $nameSingular = 'General_DaysSinceLastVisit';
    protected $columnName = 'visitor_seconds_since_last';
    protected $sqlSegment = 'FLOOR(log_visit.visitor_seconds_since_last / 86400)';
    protected $segmentName = 'daysSinceLastVisit';
}
