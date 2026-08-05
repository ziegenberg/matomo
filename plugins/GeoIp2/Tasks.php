<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\GeoIp2;

use Matomo\Plugins\UserCountry\UserCountry;
use Matomo\SettingsPiwik;

class Tasks extends \Matomo\Plugin\Tasks
{
    public function schedule()
    {
        // add the auto updater task if GeoIP admin is enabled
        if (UserCountry::isGeoLocationAdminEnabled() && SettingsPiwik::isInternetEnabled() === true) {
            $this->scheduleTask(new GeoIP2AutoUpdater());
        }
    }
}
