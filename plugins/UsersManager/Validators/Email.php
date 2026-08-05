<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\Validators;

use Matomo\Matomo;
use Matomo\Plugins\UsersManager\API as APIUsersManager;
use Matomo\Validators\BaseValidator;
use Matomo\Validators\Exception;

class Email extends BaseValidator
{
    private $checkUnique;
    private $userLogin;

    public function __construct($checkUnique = false, $userLogin = null)
    {
        $this->checkUnique = $checkUnique;
        $this->userLogin = $userLogin;
    }

    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        if (!Matomo::isValidEmailString($value)) {
            throw new Exception(Matomo::translate('General_ValidatorErrorNotEmailLike', [$value]));
        }

        if ($this->checkUnique) {
            $this->isUnique($value);
        }
    }

    /**
     * check if email already exist in database
     * @param $email
     * @throws \Exception
     */
    private function isUnique($email)
    {
        if (APIUsersManager::getInstance()->userEmailExists($email)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionEmailExists', $email));
        }

        if ($this->userLogin && mb_strtolower($this->userLogin) !== mb_strtolower($email) && APIUsersManager::getInstance()->userExists($email)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionEmailExistsAsLogin', $email));
        }

        if (!$this->userLogin && APIUsersManager::getInstance()->userExists($email)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionEmailExistsAsLogin', $email));
        }

        if (!Matomo::isValidEmailString($email)) {
            throw new Exception(Matomo::translate('UsersManager_ExceptionInvalidEmail'));
        }
    }
}
