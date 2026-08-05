<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Matomo;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class VisitTotalSearches extends VisitDimension
{
    protected $columnName = 'visit_total_searches';
    protected $columnType = 'SMALLINT(5) UNSIGNED NULL';
    protected $segmentName = 'searches';
    protected $nameSingular = 'General_NbSearches';
    protected $type = self::TYPE_NUMBER;

    public function getAcceptValues()
    {
        return Matomo::translate('Actions_SearchesSegmentHelp', '&segment=searches>0');
    }

    /**
     * @param Action|null $action
     * @return int
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        if ($this->isSiteSearchAction($action)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param Action|null $action
     * @return string|false
     */
    public function onExistingVisit(Request $request, Visitor $visitor, $action)
    {
        if ($this->isSiteSearchAction($action)) {
            return 'visit_total_searches + 1';
        }

        return false;
    }

    /**
     * @param Action|null $action
     * @return bool
     */
    private function isSiteSearchAction($action)
    {
        return ($action && $action->getActionType() == Action::TYPE_SITE_SEARCH);
    }
}
