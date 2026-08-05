<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Widgetize;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $tooltip   = Matomo::translate('Widgetize_TopLinkTooltip');
        $urlParams = $this->urlForAction('index', array('segment' => false));

        $menu->addPlatformItem('General_Widgets', $urlParams, 6, $tooltip);
    }
}
