<?php

return array(
    'Matomo\Plugins\Login\SystemSettings' => Matomo\DI::decorate(function ($settings, \Matomo\Container\Container $c) {
        /** @var \Matomo\Plugins\Login\SystemSettings $settings */
        \Matomo\Access::doAsSuperUser(function () use ($settings, $c) {
            if ($c->get('test.vars.bruteForceBlockIps')) {
                $settings->blacklistedBruteForceIps->setValue(array('10.2.3.4'));
            } elseif (\Matomo\SettingsPiwik::isMatomoInstalled()) {
                $settings->blacklistedBruteForceIps->setValue(array());
            }
        });

        return $settings;
    }),
    'Matomo\Plugins\Login\Security\BruteForceDetection' => Matomo\DI::decorate(function ($detection, \Matomo\Container\Container $c) {
        /** @var \Matomo\Plugins\Login\Security\BruteForceDetection $detection */
        if ($c->get('test.vars.bruteForceBlockIps')) {
            for ($i = 0; $i < 30; $i++) {
                // we block a random IP
                $detection->addFailedAttempt('10.55.66.77');
            }
        } elseif ($c->get('test.vars.bruteForceBlockThisIp')) {
            for ($i = 0; $i < 30; $i++) {
                // we block this IP
                $detection->addFailedAttempt(\Matomo\IP::getIpFromHeader());
            }
        } elseif (\Matomo\SettingsPiwik::isMatomoInstalled()) {
            // prevent tests from blocking other tests
            $detection->deleteAll();
        }

        return $detection;
    }),
);
