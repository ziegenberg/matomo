<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::hasUserSuperUserAccess()) {
            $systemSettings = new SystemSettings();
            if ($systemSettings->enableBruteForceDetection->getValue()) {
                $menu->addDiagnosticItem('Login_BruteForceLog', $this->urlForAction('bruteForceLog'), $orderId = 30);
            }
        }
    }
}
