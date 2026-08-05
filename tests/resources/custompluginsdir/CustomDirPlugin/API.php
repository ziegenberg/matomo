<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDirPlugin;

class API extends \Matomo\Plugin\API
{
    public function getCustomAnswerToLive($truth = true)
    {
        if ($truth) {
            return 42;
        }

        return 24;
    }
}
