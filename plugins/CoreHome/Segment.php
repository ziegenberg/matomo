<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome;

/**
 * CoreHome segment base class
 */
class Segment extends \Matomo\Plugin\Segment
{
    protected function init()
    {
        $this->setCategory('General_Visit');
    }
}
