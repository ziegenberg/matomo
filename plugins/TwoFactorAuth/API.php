<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TwoFactorAuth;

use Matomo\Matomo;

/**
 * Provides API methods for managing two-factor authentication.
 *
 * @method static \Matomo\Plugins\TwoFactorAuth\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    private TwoFactorAuthentication $twoFa;

    public function __construct(TwoFactorAuthentication $twoFa)
    {
        $this->twoFa = $twoFa;
    }

    /**
     * Disables two-factor authentication for the specified user.
     *
     * @param string $userLogin The login of the user whose two-factor authentication should be reset.
     * @param string $passwordConfirmation The current superuser's password confirmation.
     * @return void
     */
    public function resetTwoFactorAuth(
        string $userLogin,
        #[\SensitiveParameter]
        string $passwordConfirmation = ''
    ) {
        Matomo::checkUserHasSuperUserAccess();

        $this->confirmCurrentUserPassword($passwordConfirmation);
        $this->twoFa->disable2FAforUser($userLogin);
    }
}
