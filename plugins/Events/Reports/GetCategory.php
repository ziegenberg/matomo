<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Reports;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugins\Events\Columns\EventCategory;

class GetCategory extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new EventCategory();
        $this->name          = Matomo::translate('Events_EventCategories');
        $this->documentation = Matomo::translate('Events_EventCategoriesReportDocumentation');
        $this->metrics = ['nb_events', 'nb_visits', 'nb_uniq_visitors', 'sum_event_value', 'min_event_value', 'max_event_value', 'nb_events_with_value'];
        if (Common::getRequestVar('secondaryDimension', false) == 'eventName') {
            $this->actionToLoadSubTables = 'getNameFromCategoryId';
        } else {
            $this->actionToLoadSubTables = 'getActionFromCategoryId';
        }
        $this->order = 0;
    }
}
