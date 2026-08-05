<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions;

use Matomo\Common;
use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\UserPreferences;

/**
 * This class allows you to add, remove or rename menu items.
 * To configure a menu (such as Admin Menu, Reporting Menu, User Menu...) simply call the corresponding methods as
 * described in the API-Reference https://developer.matomo.org/api-reference/Piwik/Menu/MenuAbstract
 */
class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $userPreferences = new UserPreferences();
        $default = $userPreferences->getDefaultWebsiteId();
        $idSite = Common::getRequestVar('idSite', $default, 'int');

        if (Matomo::isUserHasWriteAccess($idSite)) {
            $menu->addMeasurableItem('CustomDimensions_CustomDimensions', $this->urlForAction('manage'), $orderId = 41);
        }
    }
}
