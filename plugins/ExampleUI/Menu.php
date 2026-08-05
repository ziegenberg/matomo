<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ExampleUI;

use Matomo\Menu\MenuAdmin;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $menu->addPlatformItem('ExampleUI_UiNotifications', $this->urlForAction('notifications'), $order = 10);
    }
}
