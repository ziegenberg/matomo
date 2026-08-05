<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\LanguagesManager;

use Matomo\Development;
use Matomo\Menu\MenuAdmin;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\SettingsPiwik;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        if (Matomo::isUserIsAnonymous() || !SettingsPiwik::isMatomoInstalled()) {
            $langManager = new LanguagesManager();
            $menu->addHtml('LanguageSelector', $langManager->getLanguagesSelector(), true, $order = 30, false);
        }
    }

    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Development::isEnabled() && Matomo::isUserHasSomeAdminAccess()) {
            $menu->addDevelopmentItem(
                'LanguagesManager_TranslationSearch',
                $this->urlForAction('searchTranslation')
            );
        }
    }
}
