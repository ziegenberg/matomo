<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class VisitorReturning extends VisitDimension
{
    public const IS_RETURNING_CUSTOMER = 2;
    public const IS_RETURNING = 1;
    public const IS_NEW = 0;

    protected $columnName = 'visitor_returning';
    protected $columnType = 'TINYINT(1) NULL';
    protected $segmentName = 'visitorType';
    protected $nameSingular = 'General_VisitType';
    protected $namePlural = 'General_VisitTypes';
    protected $conversionField = true;
    protected $type = self::TYPE_ENUM;

    public function __construct()
    {
        $this->acceptValues  = 'new, returning, returningCustomer. ';
        $this->acceptValues .= Matomo::translate('General_VisitTypeExample', '"&segment=visitorType==returning,visitorType==returningCustomer"');
        $this->sqlFilterValue = function ($type) {
            if (is_numeric($type)) {
                return $type;
            }
            return $type == "new" ? 0 : ($type == "returning" ? 1 : 2);
        };
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        if ($value === 1 || $value === '1' || $value === 'returning') {
            return Matomo::translate('CoreHome_VisitTypeReturning');
        } elseif ($value === 2 || $value === '2' || $value === 'returningCustomer') {
            return Matomo::translate('CoreHome_VisitTypeReturningCustomer');
        } elseif ($value === 0 || $value === '0' || $value === 'new') {
            return Matomo::translate('General_New');
        }

        return $value;
    }

    public function getEnumColumnValues()
    {
        return array(
            self::IS_RETURNING_CUSTOMER => 'returningCustomer',
            self::IS_RETURNING => 'returning',
            self::IS_NEW => 'new',
        );
    }

    /**
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $hasOrder = $visitor->getVisitorColumn('visitor_seconds_since_order')
            ?: $visitor->getPreviousVisitColumn('visitor_seconds_since_order')
            ?: $request->getParam('ec_id');
        $isReturningCustomer = (bool) $hasOrder;

        if ($isReturningCustomer) {
            return self::IS_RETURNING_CUSTOMER;
        }

        if ($visitor->isVisitorKnown()) {
            return self::IS_RETURNING;
        }

        return self::IS_NEW;
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
