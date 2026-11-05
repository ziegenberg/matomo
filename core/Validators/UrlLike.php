<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Validators;

use Matomo\Matomo;
use Matomo\UrlHelper;

class UrlLike extends BaseValidator
{
    public function validate($value)
    {
        if (!UrlHelper::isLookLikeUrl($value)) {
            throw new Exception(Matomo::translate('ValidatorErrorNotUrlLike', $value));
        }
    }
}
