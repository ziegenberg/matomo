<?php

return array(
    'Matomo\Plugins\CoreUpdater\Updater' => Matomo\DI::autowire()
        ->constructorParameter('tmpPath', Matomo\DI::get('path.tmp')),

    'diagnostics.optional' => Matomo\DI::add(array(
        Matomo\DI::get('Matomo\Plugins\CoreUpdater\Diagnostic\HttpsUpdateCheck'),
    )),
);
