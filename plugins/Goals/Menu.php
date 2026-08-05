<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals;

use Matomo\Common;
use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\UserPreferences;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $userPreferences = new UserPreferences();
        $idSite = $this->getIdSite($userPreferences->getDefaultWebsiteId());

        if (Matomo::isUserHasWriteAccess($idSite)) {
            $menu->addMeasurableItem('Goals_Goals', $this->urlForAction('manage', array('idSite' => $idSite)), 15);
        }
    }

    private function getIdSite($default = null)
    {
        $idSite = Common::getRequestVar('idSite', $default, 'int');
        return $idSite;
    }
}
