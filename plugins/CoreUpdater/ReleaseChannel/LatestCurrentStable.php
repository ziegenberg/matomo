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
use Matomo\Version;

class LatestCurrentStable extends ReleaseChannel
{
    public function getId()
    {
        // NOTE: using Version::VERSION instead of Version::MAJOR_VERSION  since MAJOR_VERSION may not exist when
        // updating from pre 4.x to 4.x.
        return 'latest_' . ((int) Version::VERSION) . 'x_stable';
    }

    public function getName()
    {
        return Matomo::translate('CoreUpdater_LatestXStableRelease', ((int) Version::VERSION) . '.X');
    }

    public function getDescription()
    {
        return Matomo::translate('CoreUpdater_LtsSupportVersion');
    }

    public function getOrder()
    {
        return 20;
    }
}
