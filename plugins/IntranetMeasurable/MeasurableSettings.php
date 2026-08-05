<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\IntranetMeasurable;

use Matomo\Settings\Setting;

class MeasurableSettings extends \Matomo\Plugins\WebsiteMeasurable\MeasurableSettings
{
    /** @var Setting */
    public $trustvisitorcookies;

    protected function shouldShowSettingsForType($type)
    {
        return $type === Type::ID;
    }
}
