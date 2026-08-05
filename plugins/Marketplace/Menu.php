<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Marketplace;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $idSiteParameter = (new SiteAwareLinks())->getIdSiteParameter();

        if (!Matomo::isUserIsAnonymous()) {
            $menu->addPlatformItem(
                'Marketplace_Marketplace',
                $this->urlForAction('overview', array_merge($idSiteParameter, ['activated' => '', 'mode' => 'admin', 'type' => '', 'show' => ''])),
                5
            );
        }

        if (Matomo::hasUserSuperUserAccess()) {
            $menu->addPluginItem(
                Matomo::translate('Marketplace_LicenseKey'),
                $this->urlForAction('manageLicenseKey', $idSiteParameter),
                10
            );
            $menu->addPluginItem(
                Matomo::translate('General_ManageSubscriptions'),
                $this->urlForAction('subscriptionOverview', $idSiteParameter),
                20
            );
        }
    }
}
