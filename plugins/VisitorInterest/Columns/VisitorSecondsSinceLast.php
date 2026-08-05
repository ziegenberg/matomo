<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest\Columns;

use Matomo\Common;
use Matomo\Date;
use Matomo\Matomo;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class VisitorSecondsSinceLast extends VisitDimension
{
    public const COLUMN_TYPE = 'INT(11) UNSIGNED NULL';

    protected $columnName = 'visitor_seconds_since_last';
    protected $columnType = self::COLUMN_TYPE;
    protected $type = self::TYPE_NUMBER;
    protected $segmentName = 'secondsSinceLastVisit';
    protected $nameSingular = 'General_SecondsSinceLastVisit';

    public function getName()
    {
        return Matomo::translate('General_SecondsSinceLastVisit');
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if (!$visitor->isVisitorKnown()) {
            return 0;
        }

        $currentTimestamp = $request->getCurrentTimestamp();

        $previousVisitFirstActionTime = $visitor->getPreviousVisitColumn('visit_first_action_time');
        if (empty($previousVisitFirstActionTime)) {
            return 0;
        }

        $previousVisitFirstActionTime = Date::factory($previousVisitFirstActionTime)->getTimestamp();

        if (empty($previousVisitFirstActionTime)) {
            Common::printDebug("Found empty visit_first_action_time for last visit of known visitor, this is unexpected.");
            return 0;
        }

        $secondsSinceLast = $currentTimestamp - $previousVisitFirstActionTime;
        if ($secondsSinceLast < 0) { // tracking a visit in the past
            return null;
        }

        return $secondsSinceLast;
    }
}
