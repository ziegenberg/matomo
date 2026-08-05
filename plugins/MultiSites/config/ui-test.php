<?php

use Matomo\Container\StaticContainer;
use Matomo\DI;

return [
    'observers.global' => DI::add([
        [
            'API.MultiSites.getAllWithGroups',
            DI::value(function (&$parameters) {
                if (StaticContainer::get('test.vars.forceMultiSitesDashboardFailure')) {
                    throw new Exception('Forced API error');
                }
            }),
        ],
    ]),
];
