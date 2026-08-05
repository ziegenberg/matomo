<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Columns;

use Matomo\Tracker\GoalManager;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class RevenueTax extends BaseConversion
{
    protected $columnName = 'revenue_tax';
    protected $type = self::TYPE_MONEY;
    protected $category = 'Goals_Ecommerce';
    protected $nameSingular = 'General_Tax';

    /**
     * @param Action|null $action
     *
     * @return mixed|false
     */
    public function onEcommerceOrderConversion(Request $request, Visitor $visitor, $action, GoalManager $goalManager)
    {
        return $this->roundRevenueIfNeeded($request->getParam('ec_tx'));
    }
}
