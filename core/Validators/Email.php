<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Validators;

use Matomo\Matomo;

class Email extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        if (!Matomo::isValidEmailString($value)) {
            throw new Exception(Matomo::translate('General_ValidatorErrorNotEmailLike', array($value)));
        }
    }
}
