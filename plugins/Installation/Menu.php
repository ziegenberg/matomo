<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Installation;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;
use Matomo\Plugin\Manager;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::hasUserSuperUserAccess() && Manager::getInstance()->isPluginActivated('Diagnostics')) {
            $menu->addDiagnosticItem(
                'Installation_SystemCheck',
                $this->urlForAction('systemCheckPage'),
                $order = 1
            );
        }
    }
}
