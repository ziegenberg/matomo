<?php

return [
    'diagnostics.optional' => Matomo\DI::add([
        Matomo\DI::get(\Matomo\Plugins\MobileMessaging\Diagnostic\MobileMessagingInformational::class),
    ]),
];
