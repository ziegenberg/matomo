<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites\Columns;

use Matomo\Columns\Dimension;
use Matomo\Matomo;

class Website extends Dimension
{
    public function getName()
    {
        return Matomo::translate('General_Website');
    }
}
