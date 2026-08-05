<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Events\Widgets;

use Matomo\Plugins\CoreHome\CoreHome;
use Matomo\Widget\WidgetContainerConfig;

class EventsByDimension extends WidgetContainerConfig
{
    protected $layout = CoreHome::WIDGET_CONTAINER_LAYOUT_BY_DIMENSION;
    protected $id = 'Events';
    protected $categoryId = 'General_Actions';
    protected $subcategoryId = 'Events_Events';
}
