<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PrivacyManager;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Matomo::isUserHasSomeAdminAccess()) {
            $category = 'PrivacyManager_MenuPrivacySettings';
            $menu->registerMenuIcon($category, 'icon-locked');
            $menu->addItem($category, null, [], 3);

            if (Matomo::hasUserSuperUserAccess()) {
                $menu->addItem($category, 'PrivacyManager_Compliance', $this->urlForAction('compliance'), 0);
                $menu->addItem($category, 'PrivacyManager_AnonymizeData', $this->urlForAction('privacySettings'), 5);
            }

            $menu->addItem($category, 'PrivacyManager_UsersOptOut', $this->urlForAction('usersOptOut'), 10);
            $menu->addItem($category, 'PrivacyManager_AskingForConsent', $this->urlForAction('consent'), 15);
            $menu->addItem($category, 'PrivacyManager_GdprOverview', $this->urlForAction('gdprOverview'), 20);
            $menu->addItem(
                $category,
                'PrivacyManager_UnderstandingYourLegalObligations',
                $this->urlForAction('understandingYourLegalObligations'),
                25
            );
            $menu->addItem($category, 'PrivacyManager_EPrivacyLaws', $this->urlForAction('ePrivacyLaws'), 30);
            $menu->addItem($category, 'PrivacyManager_GdprTools', $this->urlForAction('gdprTools'), 35);
        }
    }
}
