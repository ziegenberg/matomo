<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountryMap\Widgets;

use Matomo\Plugins\Live\Live;
use Matomo\Widget\WidgetConfig;
use Matomo\Plugin\Manager as PluginManager;

class GetRealtimeMap extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('General_Visitors');
        $config->setSubcategoryId('UserCountryMap_RealTimeMap');
        $config->setName('UserCountryMap_RealTimeMap');
        $config->setModule('UserCountryMap');
        $config->setAction('realtimeMap');
        $config->setIsWide();
        $config->setOrder(15);

        if (
            !PluginManager::getInstance()->isPluginActivated('UserCountry') ||
            !PluginManager::getInstance()->isPluginActivated('Live') ||
            !Live::isVisitorLogEnabled()
        ) {
            $config->disable();
        }
    }
}
