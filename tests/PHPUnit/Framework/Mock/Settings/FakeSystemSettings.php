<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Framework\Mock\Settings;

class FakeSystemSettings extends \Matomo\Plugins\ExampleSettingsPlugin\SystemSettings
{
    protected $pluginName = 'ExampleSettingsPlugin';

    public function init()
    {
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function makeSetting($name, $defaultValue, $type, $configureCallback)
    {
        return parent::makeSetting($name, $defaultValue, $type, $configureCallback);
    }
}
