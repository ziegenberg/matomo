<?php

return array(
    'Matomo\Plugins\TwoFactorAuth\Dao\TwoFaSecretRandomGenerator' => Matomo\DI::autowire('Matomo\Plugins\TwoFactorAuth\Dao\TwoFaSecretStaticGenerator'),
    'Matomo\Plugins\TwoFactorAuth\Dao\RecoveryCodeRandomGenerator' => Matomo\DI::autowire('Matomo\Plugins\TwoFactorAuth\Dao\RecoveryCodeStaticGenerator'),
    'Matomo\Plugins\TwoFactorAuth\TwoFactorAuthentication' => Matomo\DI::decorate(function ($previous) {
        /** @var Matomo\Plugins\TwoFactorAuth\TwoFactorAuthentication $previous */
        if (!\Matomo\SettingsPiwik::isMatomoInstalled()) {
            return $previous;
        }

        $fakeCorrectAuthCode = \Matomo\Container\StaticContainer::get('test.vars.fakeCorrectAuthCode');
        if (!empty($fakeCorrectAuthCode) && !\Matomo\Common::isPhpCliMode()) {
            $staticSecret = new \Matomo\Plugins\TwoFactorAuth\Dao\TwoFaSecretStaticGenerator();
            $secret = $staticSecret->generateSecret();

            require_once PIWIK_DOCUMENT_ROOT . '/libs/Authenticator/TwoFactorAuthenticator.php';
            $authenticator = new \TwoFactorAuthenticator();
            $_GET['authcode'] = $authenticator->getCode($secret);
            $_GET['authCode'] = $_GET['authcode'];
            $_POST['authCode'] = $_GET['authcode'];
            $_POST['authcode'] = $_GET['authcode'];
            $_REQUEST['authcode'] = $_GET['authcode'];
            $_REQUEST['authCode'] = $_GET['authcode'];
        }

        return $previous;
    }),
    'Matomo\Plugins\TwoFactorAuth\Dao\RecoveryCodeDao' => Matomo\DI::decorate(function ($previous) {
        /** @var Matomo\Plugins\TwoFactorAuth\Dao\RecoveryCodeDao $previous */
        if (!\Matomo\SettingsPiwik::isMatomoInstalled()) {
            return $previous;
        }

        $restoreCodes = \Matomo\Container\StaticContainer::get('test.vars.restoreRecoveryCodes');
        if (!empty($restoreCodes)) {
            // we ensure this recovery code always works for those users
            foreach (array('with2FA', 'with2FADisable') as $user) {
                $previous->useRecoveryCode($user, '123456'); // we are using it first to make sure there is no duplicate
                $previous->insertRecoveryCode($user, '123456');
                \Matomo\Option::deleteLike(\Matomo\Plugins\TwoFactorAuth\TwoFactorAuthentication::OPTION_PREFIX_TWO_FA_CODE_USED . '%');
            }
        }

        return $previous;
    }),
    'Matomo\Plugins\TwoFactorAuth\SystemSettings' => Matomo\DI::decorate(function ($previous) {
        /** @var Matomo\Plugins\TwoFactorAuth\SystemSettings $previous */
        if (!\Matomo\SettingsPiwik::isMatomoInstalled()) {
            return $previous;
        }

        Matomo\Access::doAsSuperUser(function () use ($previous) {
            $requireTwoFa = \Matomo\Container\StaticContainer::get('test.vars.requireTwoFa');
            if (!empty($requireTwoFa)) {
                $previous->twoFactorAuthRequired->setValue(1);
            } else {
                try {
                    $previous->twoFactorAuthRequired->setValue(0);
                } catch (Exception $e) {
                    // may fail when matomo is trying to update or so
                }
            }
        });

        return $previous;
    }),
);
