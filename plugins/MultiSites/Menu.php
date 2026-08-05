<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites;

use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\Request;

class Menu extends \Matomo\Plugin\Menu
{
    public function configureTopMenu(MenuTop $menu)
    {
        $idSite = Request::fromRequest()->getIntegerParameter('idSite', 0);

        $urlParams = $this->urlForActionWithDefaultUserParams('index', ['segment' => false, 'idSite' => $idSite ?: false]);
        $tooltip   = Matomo::translate('MultiSites_TopLinkTooltip');

        $menu->addItem('General_MultiSitesSummary', null, $urlParams, 3, $tooltip);
    }
}
