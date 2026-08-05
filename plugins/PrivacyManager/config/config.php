<?php

return array(

    'diagnostics.informational' => Matomo\DI::add(array(
        Matomo\DI::get('Matomo\Plugins\PrivacyManager\Diagnostic\PrivacyInformational'),
    )),
);
