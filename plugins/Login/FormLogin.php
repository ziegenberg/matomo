<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Login;

use HTML_QuickForm2_DataSource_Array;
use Matomo\Matomo;
use Matomo\QuickForm2;

class FormLogin extends QuickForm2
{
    public function __construct($id = 'login_form', $method = 'post', $attributes = null, $trackSubmit = false)
    {
        $attributes = array_merge($attributes ?: [], [ 'action' => '?module=' . Matomo::getLoginPluginName() ]);
        parent::__construct($id, $method, $attributes, $trackSubmit);
    }

    public function init()
    {
        $this->addElement('text', 'form_login')
            ->addRule('required', Matomo::translate('General_Required', Matomo::translate('Login_LoginOrEmail')));

        $this->addElement('password', 'form_password')
            ->addRule('required', Matomo::translate('General_Required', Matomo::translate('General_Password')));

        $this->addElement('hidden', 'form_redirect');

        $this->addElement('hidden', 'form_nonce');

        $this->addElement('checkbox', 'form_rememberme');

        $this->addElement('submit', 'submit');

        // default values
        $this->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
                                                                       'form_rememberme' => 0,
                                                                  )));
    }
}
