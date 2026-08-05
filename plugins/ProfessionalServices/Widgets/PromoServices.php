<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ProfessionalServices\Widgets;

use Matomo\Container\StaticContainer;
use Matomo\Plugins\ProfessionalServices\Promo;
use Matomo\ProfessionalServices\Advertising;
use Matomo\View;
use Matomo\Widget\WidgetConfig;

class PromoServices extends \Matomo\Widget\Widget
{
    private Advertising $advertising;

    private Promo $promo;

    public function __construct(Advertising $advertising, Promo $promo)
    {
        $this->advertising = $advertising;
        $this->promo = $promo;
    }

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('About Matomo');
        $config->setName('ProfessionalServices_WidgetPremiumServicesForPiwik');

        $advertising = StaticContainer::get('Matomo\ProfessionalServices\Advertising');
        $config->setIsEnabled($advertising->areAdsForProfessionalServicesEnabled());
    }

    public function render()
    {
        $view = new View('@ProfessionalServices/promoServicesWidget');

        $promo = $this->promo->getContent();

        $view->ctaLinkUrl = $promo['url'];
        $view->ctaText = $promo['text'];
        $view->ctaTitle = $promo['title'];
        $view->ctaLinkTitle = $this->promo->getLinkTitle();

        return $view->render();
    }
}
