<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Marketplace\Widgets;

use Matomo\Matomo;
use Matomo\Plugins\Marketplace\Api\Client;
use Matomo\Plugins\Marketplace\Input\PurchaseType;
use Matomo\Plugins\Marketplace\Input\Sort;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;

class GetPremiumFeatures extends Widget
{
    private Client $marketplaceApiClient;

    public function __construct(Client $marketplaceApiClient)
    {
        $this->marketplaceApiClient = $marketplaceApiClient;
    }

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Marketplace_Marketplace');
        $config->setSubcategoryId('Marketplace_PaidPlugins');
        $config->setName('Marketplace_PaidPlugins');
        $config->setOrder(20);
        $config->setIsEnabled(!Matomo::isUserIsAnonymous());
    }

    public function render()
    {
        Matomo::checkUserIsNotAnonymous();
        $template = 'getPremiumFeatures';

        $plugins = $this->marketplaceApiClient->searchForPlugins('', '', Sort::METHOD_LAST_UPDATED, PurchaseType::TYPE_PAID);

        //sort array by bundle first
        usort($plugins, function ($item1, $item2) {
            return $item1['isBundle'] < $item2['isBundle'] ? 1 : -1;
        });

        if (empty($plugins)) {
            $plugins = array();
        } else {
            $plugins = array_splice($plugins, 0, 20);
        }

        return $this->renderTemplate($template, array(
            'plugins' => $plugins,
        ));
    }
}
