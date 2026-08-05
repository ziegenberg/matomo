<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Widgets;

use Matomo\Common;
use Matomo\Plugins\CoreHome\CoreHome;
use Matomo\Widget\WidgetContainerConfig;
use Matomo\Site;

class ProductsByDimension extends WidgetContainerConfig
{
    protected $layout = CoreHome::WIDGET_CONTAINER_LAYOUT_BY_DIMENSION;
    protected $id = 'Products';
    protected $categoryId = 'Goals_Ecommerce';
    protected $subcategoryId = 'Goals_Products';

    public function isEnabled()
    {
        $idSite = Common::getRequestVar('idSite', false, 'int');

        if (empty($idSite)) {
            return false;
        }

        $site = new Site($idSite);
        return $site->isEcommerceEnabled();
    }
}
