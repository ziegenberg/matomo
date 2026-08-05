<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ProfessionalServices\Widgets;

use Matomo\Container\StaticContainer;
use Matomo\Plugins\Marketplace\SiteAwareLinks;
use Matomo\Matomo;
use Matomo\View;
use Matomo\Widget\WidgetConfig;

class PromoCrashAnalytics extends DismissibleWidget
{
    private const PROMO_PLUGIN_NAME = 'CrashAnalytics';

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('ProfessionalServices_PromoCrashAnalytics');
        $config->setSubcategoryId('ProfessionalServices_PromoOverview');
        $config->setIsNotWidgetizable();

        $promoWidgetApplicable = StaticContainer::get('Matomo\Plugins\ProfessionalServices\PromoWidgetApplicable');

        $isEnabled = $promoWidgetApplicable->check(self::PROMO_PLUGIN_NAME, self::getDismissibleWidgetName());
        $config->setIsEnabled($isEnabled);
    }

    public function render()
    {
        $marketplacePlugins = StaticContainer::get('Matomo\Plugins\Marketplace\Plugins');
        $pluginInfo = $marketplacePlugins->getPluginInfo(self::PROMO_PLUGIN_NAME);

        $view = new View('@ProfessionalServices/pluginAdvertising');
        $view->plugin = $pluginInfo;
        $view->widgetName = self::getDismissibleWidgetName();
        $view->userCanDismiss = Matomo::isUserIsAnonymous() === false;
        $view->marketplaceOverviewLink = (new SiteAwareLinks())->getOverviewUrl($pluginInfo['name']);

        $view->title  = Matomo::translate('ProfessionalServices_PromoUnlockPowerOf', $pluginInfo['displayName']);
        $view->listOfFeatures = [
            Matomo::translate('ProfessionalServices_CrashAnalyticsFeature01'),
            Matomo::translate('ProfessionalServices_CrashAnalyticsFeature02'),
            Matomo::translate('ProfessionalServices_CrashAnalyticsFeature03'),
        ];

        return $view->render();
    }
}
