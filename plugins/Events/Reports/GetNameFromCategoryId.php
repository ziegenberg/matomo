<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Events\Columns\EventName;

/**
 * Report metadata class for the Events.getNameFromCategoryId class.
 */
class GetNameFromCategoryId extends Base
{
    protected function init()
    {
        $this->categoryId = 'Events_Events';
        $this->processedMetrics = [];

        $this->dimension     = new EventName();
        $this->name          = Matomo::translate('Events_EventNames');
        $this->isSubtableReport = true;
    }
}
