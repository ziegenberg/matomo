<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Events\Columns\EventAction;

/**
 * Report metadata class for the Events.getActionFromNameId class.
 */
class GetActionFromNameId extends Base
{
    protected function init()
    {
        parent::init();

        $this->dimension     = new EventAction();
        $this->name          = Matomo::translate('Events_EventActions');
        $this->isSubtableReport = true;
    }
}
