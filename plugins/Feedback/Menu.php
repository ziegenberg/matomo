<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Feedback;

use Matomo\Menu\MenuTop;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        $menu->registerMenuIcon('General_Help', 'icon-help');
        $menu->addItem('General_Help', null, array('module' => 'Feedback', 'action' => 'index'), $order = 990, Matomo::translate('General_Help'));
    }
}
