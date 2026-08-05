<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome;

use Matomo\Menu\MenuAdmin;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\Changes\UserChanges;
use Matomo\Changes\Model as ChangesModel;
use Matomo\Plugins\UsersManager\Model as UsersModel;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $menu->addPersonalItem('', [], 1);
        $menu->addSystemItem('', [], 2);
        $menu->addPluginItem('', [], 3);
        $menu->addMeasurableItem('', [], 4);
        $menu->addPlatformItem('', [], 5);
        $menu->addDiagnosticItem('', [], 6);
        $menu->addDevelopmentItem('', [], 40);

        if (Matomo::hasUserSuperUserAccess()) {
            $menu->addSystemItem(
                'General_GeneralSettings',
                $this->urlForAction('generalSettings'),
                $order = 5
            );
        }

        if (!Matomo::isUserIsAnonymous()) {
            $menu->addMeasurableItem(
                'CoreAdminHome_TrackingCode',
                $this->urlForAction('trackingCodeGenerator'),
                $order = 12
            );
        }

        if (Matomo::isUserHasSomeAdminAccess()) {
            $menu->addDiagnosticItem(
                'CoreAdminHome_TrackingFailures',
                $this->urlForAction('trackingFailures'),
                $order = 2
            );
        }
    }

    public function configureTopMenu(MenuTop $menu)
    {
        $url = $this->urlForModuleAction('CoreAdminHome', 'home');
        $menu->registerMenuIcon('CoreAdminHome_Administration', 'icon-settings');
        $menu->addItem('CoreAdminHome_Administration', null, $url, 980, Matomo::translate('CoreAdminHome_Administration'));

        if (!Matomo::isUserIsAnonymous() && Matomo::isUserHasSomeViewAccess()) {
            $model = new UsersModel();
            $user = $model->getUser(Matomo::getCurrentUserLogin());
            if ($user) {
                $userChanges = new UserChanges($user);
                $newChangesStatus = $userChanges->getNewChangesStatus();

                if ($newChangesStatus !== ChangesModel::NO_CHANGES_EXIST) {
                    $icon = ($newChangesStatus === ChangesModel::NEW_CHANGES_EXIST ? 'icon-notifications_on' : 'icon-reporting-actions');

                    $menu->registerMenuIcon('CoreAdminHome_WhatIsNew', $icon);
                    $menu->addItem(
                        'CoreAdminHome_WhatIsNew',
                        null,
                        'javascript:',
                        990,
                        Matomo::translate('CoreAdminHome_WhatIsNewTooltip'),
                        $icon,
                        "Piwik_Popover.createPopupAndLoadUrl('module=CoreAdminHome&action=whatIsNew', '" .
                        addslashes(Matomo::translate('CoreAdminHome_WhatIsNewTooltip')) . "','what-is-new-popup')",
                        null,
                        null,
                        $userChanges->getNewChangesCount()
                    );
                }
            }
        }
    }
}
