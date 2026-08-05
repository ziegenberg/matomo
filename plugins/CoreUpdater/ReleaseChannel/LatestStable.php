<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreUpdater\ReleaseChannel;

use Matomo\Matomo;
use Matomo\Plugins\CoreUpdater\ReleaseChannel;

class LatestStable extends ReleaseChannel
{
    public function getId()
    {
        return 'latest_stable';
    }

    public function getName()
    {
        return Matomo::translate('CoreUpdater_LatestStableRelease');
    }

    public function getDescription()
    {
        return Matomo::translate('General_Recommended');
    }

    public function getOrder()
    {
        return 10;
    }
}
