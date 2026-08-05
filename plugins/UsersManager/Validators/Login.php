<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\Validators;

use Matomo\Matomo;
use Matomo\SettingsPiwik;
use Matomo\Plugins\UsersManager\API as APIUsersManager;
use Matomo\Validators\BaseValidator;
use Matomo\Validators\Exception;

class Login extends BaseValidator
{
    public const LOGIN_MIN_LENGTH = 2;
    public const LOGIN_MAX_LENGTH = 100;

    private $checkUnique;

    public function __construct($checkUnique = false)
    {
        $this->checkUnique = $checkUnique;
    }

    public function validate($value)
    {
        if (
            !SettingsPiwik::isUserCredentialsSanityCheckEnabled()
            && !empty($value)
        ) {
            return;
        }

        $l = strlen($value);
        if (
            !($l >= self::LOGIN_MIN_LENGTH
            && $l <= self::LOGIN_MAX_LENGTH
            && (preg_match('/^[A-Za-zÄäÖöÜüß0-9_.@+-]*$/D', $value) > 0))
        ) {
            throw new Exception(Matomo::translate(
                'UsersManager_ExceptionInvalidLoginFormat',
                [self::LOGIN_MIN_LENGTH, self::LOGIN_MAX_LENGTH]
            ));
        }

        if ($this->checkUnique) {
            $this->isUnique($value);
        }
    }

    /**
     * check if login already exist in database
     * @param $login
     * @throws \Exception
     */
    private function isUnique($login)
    {
        if (APIUsersManager::getInstance()->userExists($login)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionLoginExists', $login));
        }

        if (APIUsersManager::getInstance()->userEmailExists($login)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionLoginExistsAsEmail', $login));
        }
    }
}
