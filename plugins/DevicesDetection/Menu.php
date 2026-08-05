<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicesDetection;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::isUserHasSomeAdminAccess()) {
            $menu->addDiagnosticItem(
                'DevicesDetection_DeviceDetection',
                $this->urlForAction('detection'),
                $order = 40
            );
        }
    }
}
