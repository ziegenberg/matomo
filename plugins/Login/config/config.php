<?php

use Matomo\Container\StaticContainer;
use Matomo\Auth\PasswordStrength;

return [
    'Matomo\Auth' => Matomo\DI::create('Matomo\Plugins\Login\Auth'),
    'Matomo\Auth\PasswordStrength' => Matomo\DI::factory(function () {
        $settings = StaticContainer::get('Matomo\Plugins\Login\SystemSettings');
        $featureEnabled = $settings->enablePasswordStrengthCheck->getValue();
        return new PasswordStrength($featureEnabled);
    }),
];
