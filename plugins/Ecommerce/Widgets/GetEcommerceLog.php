<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Widgets;

use Matomo\Common;
use Matomo\Plugin\Manager;
use Matomo\Plugins\Live\Live;
use Matomo\Widget\WidgetConfig;
use Matomo\Site;

class GetEcommerceLog extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Goals_Ecommerce');
        $config->setSubcategoryId('Goals_EcommerceLog');
        $config->setName('Goals_EcommerceLog');

        $idSite = Common::getRequestVar('idSite', 0, 'int');
        if (empty($idSite)) {
            $config->disable();
            return;
        }

        $site = new Site($idSite);
        $config->setIsEnabled($site->isEcommerceEnabled());

        if (!Manager::getInstance()->isPluginActivated('Live') || !Live::isVisitorLogEnabled($idSite)) {
            $config->disable();
        }
    }
}
