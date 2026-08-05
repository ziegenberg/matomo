<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::isUserHasSomeAdminAccess() && UsersManager::isUsersAdminEnabled()) {
            $menu->addSystemItem('UsersManager_MenuUsers', $this->urlForAction('index'), $order = 15);
        }

        if (Matomo::hasUserSuperUserAccess() && API::getInstance()->getSitesAccessFromUser('anonymous')) {
            $menu->addSystemItem('UsersManager_AnonymousUser', $this->urlForAction('anonymousSettings'), $order = 16);
        }

        if (!Matomo::isUserIsAnonymous()) {
            $menu->addItem('UsersManager_MenuPersonal', 'General_Settings', $this->urlForAction('userSettings'), 0);
            $menu->addItem('UsersManager_MenuPersonal', 'General_Security', $this->urlForAction('userSecurity'), 1);
        }
    }
}
