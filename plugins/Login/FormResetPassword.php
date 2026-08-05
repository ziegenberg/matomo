<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login;

use Matomo\Matomo;
use Matomo\QuickForm2;

class FormResetPassword extends QuickForm2
{
    public function __construct($id = 'resetpasswordform', $method = 'post', $attributes = null, $trackSubmit = false)
    {
        parent::__construct($id, $method, $attributes, $trackSubmit);
    }

    public function init()
    {
        $this->addElement('text', 'form_login')
            ->addRule('required', Matomo::translate('General_Required', Matomo::translate('Login_LoginOrEmail')));

        $password = $this->addElement('password', 'form_password');
        $password->addRule('required', Matomo::translate('General_Required', Matomo::translate('General_Password')));

        $passwordBis = $this->addElement('password', 'form_password_bis');
        $passwordBis->addRule('required', Matomo::translate('General_Required', Matomo::translate('Login_PasswordRepeat')));
        $passwordBis->addRule('eq', Matomo::translate('Login_PasswordsDoNotMatch'), ['operand' => $password]);

        $this->addElement('hidden', 'form_nonce');

        $this->addElement('submit', 'submit');
    }
}
