<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Settings\Plugin;

use Matomo\Settings\Plugin\UserSetting;
use Matomo\Settings\Plugin\UserSettings;
use Matomo\Tests\Framework\Mock\Settings\FakeUserSettings;
use Matomo\Tests\Integration\Settings\BaseSettingsTestCase;

/**
 * @group PluginSettings
 * @group UserSettings
 */
class UserSettingsTest extends BaseSettingsTestCase
{
    protected $updateEventName = 'UserSettings.updated';

    protected function createSettingsInstance()
    {
        return new FakeUserSettings();
    }

    public function testWeAreWorkingWithUserSettings()
    {
        $this->assertTrue($this->settings instanceof UserSettings);
    }

    public function testConstructorGetPluginNameCanDetectPluginNameAutomatically()
    {
        $settings = new \Matomo\Plugins\ExampleSettingsPlugin\UserSettings();
        $this->assertSame('ExampleSettingsPlugin', $settings->getPluginName());
        $this->assertSame('ExampleSettingsPlugin', $this->settings->getPluginName());
    }

    public function testMakeSettingShouldCreateAUserSetting()
    {
        $setting = $this->makeSetting('myName');

        $this->assertTrue($setting instanceof UserSetting);
    }
}
