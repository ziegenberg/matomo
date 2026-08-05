<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Framework\Mock\Settings;

class FakeMeasurableSettings extends \Matomo\Plugins\ExampleSettingsPlugin\MeasurableSettings
{
    protected $pluginName = 'ExampleSettingsPlugin';

    public function init()
    {
    }

    public function makeSetting($name, $defaultValue, $type, $fieldConfigCallback)
    {
        return parent::makeSetting($name, $defaultValue, $type, $fieldConfigCallback);
    }

    public function makeProperty($name, $defaultValue, $type, $configureCallback)
    {
        return parent::makeProperty($name, $defaultValue, $type, $configureCallback);
    }
}
