<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MobileMessaging;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $title = 'MobileMessaging_SettingsMenu';
        $url = $this->urlForAction('index');
        $order = 35;

        if (Matomo::hasUserSuperUserAccess()) {
            $menu->addSystemItem($title, $url, $order);
        } elseif (!Matomo::isUserIsAnonymous()) {
            $menu->addPersonalItem($title, $url, $order);
        }
    }
}
