<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Categories;

use Matomo\Category\Subcategory;
use Matomo\Matomo;
use Matomo\Url;

class EventsSubcategory extends Subcategory
{
    protected $categoryId = 'General_Actions';
    protected $id = 'Events_Events';
    protected $order = 40;

    public function getHelp()
    {
        return '<p>' . Matomo::translate('Events_EventsSubcategoryHelp1') . '</p>'
            . '<p>' . Url::getExternalLinkTag('https://matomo.org/docs/event-tracking/', null, null, 'App.Events.getCategory')
            . Matomo::translate('Events_EventsSubcategoryHelp2') . '</a></p>';
    }
}
