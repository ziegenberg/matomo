<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DBStats;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::hasUserSuperUserAccess()) {
            $menu->addDiagnosticItem(
                'DBStats_DatabaseUsage',
                $this->urlForAction('index'),
                $order = 6
            );
        }
    }
}
