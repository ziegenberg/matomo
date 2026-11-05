<?php

return array(

    'Matomo\Cache\Backend' => Matomo\DI::autowire('Matomo\Cache\Backend\ArrayCache'),

    'Matomo\Translation\Loader\LoaderInterface' => Matomo\DI::autowire('Matomo\Translation\Loader\LoaderCache')
        ->constructorParameter('loader', Matomo\DI::get('Matomo\Translation\Loader\DevelopmentLoader')),
    'Matomo\Translation\Loader\DevelopmentLoader' => Matomo\DI::create()
        ->constructor(Matomo\DI::get('Matomo\Translation\Loader\JsonFileLoader')),

);
