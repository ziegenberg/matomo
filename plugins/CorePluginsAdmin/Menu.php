<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CorePluginsAdmin;

use Matomo\Menu\MenuAdmin;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\Plugins\CorePluginsAdmin\Model\TagManagerTeaser;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        $tagManagerTeaser = new TagManagerTeaser(Matomo::getCurrentUserLogin());

        if ($tagManagerTeaser->shouldShowTeaser()) {
            $menu->addItem('Tag Manager', null, $this->urlForAction('tagManagerTeaser'));
        }
    }

    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (!Matomo::isUserIsAnonymous()) {
            $menu->addPlatformItem('', [], 7);
        }

        if (Matomo::hasUserSuperUserAccess()) {
            $menu->addPluginItem(
                Matomo::translate('General_ManagePlugins'),
                $this->urlForAction('plugins', ['activated' => '']),
                10,
                false,
                'manage-plugins'
            );
        }
    }
}
