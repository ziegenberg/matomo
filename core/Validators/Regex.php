<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Validators;

use Matomo\Matomo;

class Regex extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        if (@preg_match($value, '') === false) {
            throw new Exception(Matomo::translate('General_ValidatorErrorNoValidRegex', array($value)));
        }
    }
}
