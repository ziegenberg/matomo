<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Widgets;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Widget\WidgetConfig;

class GetCampaignUrlBuilder extends \Matomo\Widget\Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Referrers_Referrers');
        $config->setSubcategoryId('Referrers_URLCampaignBuilder');
        $config->setName('Referrers_URLCampaignBuilder');
        $config->setClientSideComponent('Referrers', 'CampaignBuilderWidget');
        $config->setClientSideProps([
            'hasExtraPlugin' => Plugin\Manager::getInstance()->isPluginActivated('MarketingCampaignsReporting'),
        ]);

        $idSite = self::getIdSite();
        if (!Matomo::isUserHasViewAccess($idSite)) {
            $config->disable();
        }
    }

    private static function getIdSite()
    {
        return Common::getRequestVar('idSite', 0, 'int');
    }
}
