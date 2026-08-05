<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Tour\Engagement;

use Matomo\Matomo;
use Matomo\Plugins\TwoFactorAuth\TwoFactorAuthentication;
use Matomo\Url;

class ChallengeSetupTwoFa extends Challenge
{
    public function getName()
    {
        return Matomo::translate('Tour_SetupX', Matomo::translate('TwoFactorAuth_TwoFactorAuthentication'));
    }

    public function getDescription()
    {
        return Matomo::translate('TwoFactorAuth_TwoFactorAuthenticationIntro', array('', ''));
    }

    public function getId()
    {
        return 'setup_twofa';
    }

    public function isCompleted(string $login)
    {
        return TwoFactorAuthentication::isUserUsingTwoFactorAuthentication($login);
    }

    public function getUrl()
    {
        return Url::addCampaignParametersToMatomoLink('https://matomo.org/faq/general/faq_27245');
    }
}
