<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\SegmentEditor\Widgets;

use Matomo\Widget\WidgetConfig;

class ManageSegments extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $idSite = \Matomo\Request::fromRequest()->getIntegerParameter('idSite', 0);

        $config->setCategoryId('General_Visitors');
        $config->setSubcategoryId('CoreHome_Segments');
        $config->setName('CoreHome_Segments');
        $config->setIsNotWidgetizable();

        if (empty($idSite)) {
            $config->disable();
        }
    }
}
