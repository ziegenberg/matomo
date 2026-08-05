<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Settings\Plugin;

use Matomo\Db;
use Matomo\Plugins\WebsiteMeasurable\Type;
use Matomo\Settings\Measurable\MeasurableSetting;
use Matomo\Settings\Measurable\MeasurableSettings;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\Mock\Settings\FakeMeasurableSettings;
use Matomo\Tests\Integration\Settings\BaseSettingsTestCase;

/**
 * @group PluginSettings
 * @group UserSettings
 */
class MeasurableSettingsTest extends BaseSettingsTestCase
{
    protected $updateEventName = 'MeasurableSettings.updated';

    protected function createSettingsInstance()
    {
        if (!Fixture::siteCreated(1)) {
            Fixture::createWebsite('2014-01-01 00:00:01');
        }
        Db::destroyDatabaseObject();
        return new FakeMeasurableSettings($idSite = 1, $type = Type::ID);
    }

    public function testWeAreWorkingWithMeasurableSettings()
    {
        $this->assertTrue($this->settings instanceof MeasurableSettings);
    }

    public function testConstructorGetPluginNameCanDetectPluginNameAutomatically()
    {
        $this->assertSame('ExampleSettingsPlugin', $this->settings->getPluginName());

        $settings = new \Matomo\Plugins\ExampleSettingsPlugin\MeasurableSettings($idSite = 1);
        $this->assertSame('ExampleSettingsPlugin', $settings->getPluginName());
    }

    public function testMakeSettingShouldCreateAMeasurableSetting()
    {
        $setting = $this->makeSetting('myName');

        $this->assertTrue($setting instanceof MeasurableSetting);
    }
}
