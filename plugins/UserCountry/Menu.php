<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (UserCountry::isGeoLocationAdminEnabled() && Matomo::hasUserSuperUserAccess()) {
            $menu->addSystemItem(
                'UserCountry_Geolocation',
                $this->urlForAction('adminIndex'),
                $order = 30
            );
        }
    }
}
