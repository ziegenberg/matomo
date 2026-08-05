<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TwoFactorAuth;

use Matomo\Matomo;
use Matomo\QuickForm2;

class FormTwoFactorAuthCode extends QuickForm2
{
    public function __construct($id = 'login_form', $method = 'post', $attributes = null, $trackSubmit = false)
    {
        parent::__construct($id, $method, $attributes, $trackSubmit);
    }

    public function init()
    {
        $this->addElement('text', 'form_authcode')
            ->addRule(
                'required',
                Matomo::translate('General_Required', 'Authentication code')
            );

        $this->addElement('hidden', 'form_nonce');

        $this->addElement('submit', 'submit');
    }
}
