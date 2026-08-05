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

class LatestBeta extends ReleaseChannel
{
    public function getId()
    {
        return 'latest_beta';
    }

    public function getName()
    {
        return Matomo::translate('CoreUpdater_LatestBetaRelease');
    }

    public function doesPreferStable()
    {
        return false;
    }

    public function getOrder()
    {
        return 11;
    }
}
