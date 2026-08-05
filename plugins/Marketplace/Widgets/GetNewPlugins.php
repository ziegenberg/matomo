<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Marketplace\Widgets;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugins\Marketplace\Api\Client;
use Matomo\Plugins\Marketplace\Input\PurchaseType;
use Matomo\Plugins\Marketplace\Input\Sort;
use Matomo\Widget\Widget;
use Matomo\Widget\WidgetConfig;

class GetNewPlugins extends Widget
{
    private Client $marketplaceApiClient;

    public function __construct(Client $marketplaceApiClient)
    {
        $this->marketplaceApiClient = $marketplaceApiClient;
    }

    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId('Marketplace_Marketplace');
        $config->setName('Marketplace_LatestMarketplaceUpdates');
        $config->setOrder(19);
        $config->setIsEnabled(!Matomo::isUserIsAnonymous());
    }

    public function render()
    {
        Matomo::checkUserIsNotAnonymous();

        $isAdminPage = Common::getRequestVar('isAdminPage', 0, 'int');

        if (!empty($isAdminPage)) {
            $template = 'getNewPluginsAdmin';
        } else {
            $template = 'getNewPlugins';
        }

        $plugins = $this->marketplaceApiClient->searchForPlugins('', '', Sort::METHOD_LAST_UPDATED, PurchaseType::TYPE_ALL);

        $plugins = array_filter($plugins, function ($plugin) {
            return empty($plugin['isBundle']);
        });

        return $this->renderTemplate($template, array(
            'plugins' => array_splice($plugins, 0, 3),
        ));
    }
}
