<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Framework\Mock\Settings;

class FakeUserSettings extends \Matomo\Plugins\ExampleSettingsPlugin\UserSettings
{
    protected $pluginName = 'ExampleSettingsPlugin';

    public function init()
    {
    }

    public function makeSetting($name, $defaultValue, $type, $configureCallback)
    {
        return parent::makeSetting($name, $defaultValue, $type, $configureCallback);
    }
}
