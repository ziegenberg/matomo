<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TestRunner;

class Controller extends \Matomo\Plugin\Controller
{
    public function check()
    {
        return 'OK';
    }
}
