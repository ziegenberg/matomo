<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ScheduledReports;

use Matomo\Menu\MenuAdmin;
use Matomo\Matomo;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureAdminMenu(MenuAdmin $menu)
    {
        $tooltip = Matomo::translate(
            \Matomo\Plugin\Manager::getInstance()->isPluginActivated('MobileMessaging')
            ? 'MobileMessaging_TopLinkTooltip' : 'ScheduledReports_TopLinkTooltip'
        );

        $menu->addPersonalItem(
            'ScheduledReports_ScheduleReports',
            $this->urlForAction('index', array('segment' => false)),
            7,
            $tooltip
        );
    }
}
