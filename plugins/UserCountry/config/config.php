<?php

return array(
    'diagnostics.optional' => Matomo\DI::add(array(
        Matomo\DI::get('Matomo\Plugins\UserCountry\Diagnostic\GeolocationDiagnostic'),
    )),
);
