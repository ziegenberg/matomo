<?php

use Matomo\Config\GeneralConfig;
use Matomo\Plugins\Marketplace\Api\Service;
use Matomo\Plugins\Marketplace\LicenseKey;
use Matomo\Container\Container;

return array(
    'MarketplaceEndpoint' => function (Container $c) {

        $domain = 'plugins.matomo.org';

        if (GeneralConfig::getConfigValue('force_matomo_http_request') == 1) {
            return 'http://' . $domain;
        }

        return 'https://' . $domain;
    },
    'Matomo\Plugins\Marketplace\Api\Service' => function (Container $c) {
        $domain = $c->get('MarketplaceEndpoint');

        $service = new Service($domain);

        $key = new LicenseKey();
        $accessToken = $key->get();

        $service->authenticate($accessToken);

        return $service;
    },
);
