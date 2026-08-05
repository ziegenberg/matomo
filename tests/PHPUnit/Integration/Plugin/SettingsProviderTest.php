<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Plugin;

use Matomo\Container\StaticContainer;
use Matomo\Plugin;
use Matomo\Plugin\SettingsProvider;
use Matomo\Settings\Measurable\MeasurableSettings;
use Matomo\Settings\Plugin\SystemSettings;
use Matomo\Settings\Plugin\UserSettings;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group SettingsProvider
 * @group SettingsProviderTest
 */
class SettingsProviderTest extends IntegrationTestCase
{
    /**
     * @var SettingsProvider
     */
    private $settings;

    /**
     * @var Plugin\Manager
     */
    private $pluginManager;

    private $examplePlugin = 'ExampleSettingsPlugin';

    public function setUp(): void
    {
        parent::setUp();

        $_GET['idSite'] = 1;
        if (!Fixture::siteCreated(1)) {
            Fixture::createWebsite('2015-01-01 00:00:00');
        }

        $this->pluginManager = StaticContainer::get('Matomo\Plugin\Manager');
        $this->settings = new SettingsProvider($this->pluginManager);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($_GET['idSite']);
    }

    public function testGetSystemSettingsShouldFindASystemSettingOfPlugin()
    {
        $settings = $this->settings->getSystemSettings($this->examplePlugin);

        $this->assertTrue($settings instanceof SystemSettings);
        $this->assertSame($this->examplePlugin, $settings->getPluginName());
    }

    public function testGetSystemSettingsShouldReturnNullIfPluginHasNoSystemSettings()
    {
        $settings = $this->settings->getSystemSettings('Intl');

        $this->assertNull($settings);
    }

    public function testGetSystemSettingsShouldReturnNullIfPluginHasSettingButIsNotLoaded()
    {
        $this->pluginManager->unloadPlugin($this->examplePlugin);
        $settings = $this->settings->getSystemSettings($this->examplePlugin);
        $this->pluginManager->loadPlugin($this->examplePlugin);

        $this->assertNull($settings);
    }

    public function testGetAllSystemSettingsShouldFindAllSystemSettings()
    {
        $settings = $this->settings->getAllSystemSettings();

        $this->assertArrayHasKey($this->examplePlugin, $settings);
        $this->assertArrayHasKey('AnonymousPiwikUsageMeasurement', $settings);
        $this->assertArrayHasKey('QueuedTracking', $settings);

        foreach ($settings as $setting) {
            $this->assertTrue($setting instanceof SystemSettings);
        }
    }

    public function testGetUserSettingsShouldFindASystemSettingOfPlugin()
    {
        $settings = $this->settings->getUserSettings($this->examplePlugin);

        $this->assertTrue($settings instanceof UserSettings);
        $this->assertSame($this->examplePlugin, $settings->getPluginName());
    }

    public function testGetUserSettingsShouldReturnNullIfPluginHasNoSystemSettings()
    {
        $settings = $this->settings->getUserSettings('Intl');

        $this->assertNull($settings);
    }

    public function testGetUserSettingsShouldReturnNullIfPluginHasSettingButIsNotLoaded()
    {
        $this->pluginManager->unloadPlugin($this->examplePlugin);
        $settings = $this->settings->getUserSettings($this->examplePlugin);
        $this->pluginManager->loadPlugin($this->examplePlugin);

        $this->assertNull($settings);
    }

    public function testGetAllUserSettingsShouldFindAllSystemSettings()
    {
        $settings = $this->settings->getAllUserSettings();

        $this->assertArrayHasKey($this->examplePlugin, $settings);

        foreach ($settings as $setting) {
            $this->assertTrue($setting instanceof UserSettings);
        }
    }

    public function testGetMeasurableSettingsShouldFindASystemSettingOfPlugin()
    {
        $settings = $this->settings->getMeasurableSettings($this->examplePlugin, $idSite = 1, $idType = null);

        $this->assertTrue($settings instanceof MeasurableSettings);
        $this->assertSame($this->examplePlugin, $settings->getPluginName());
    }

    public function testGetMeasurableSettingsShouldReturnNullIfPluginHasNoSystemSettings()
    {
        $settings = $this->settings->getMeasurableSettings('Intl', $idSite = 1, $idType = null);

        $this->assertNull($settings);
    }

    public function testGetMeasurableSettingsShouldReturnNullIfPluginHasSettingButIsNotLoaded()
    {
        $this->pluginManager->unloadPlugin($this->examplePlugin);
        $settings = $this->settings->getMeasurableSettings($this->examplePlugin, $idSite = 1, $idType = null);
        $this->pluginManager->loadPlugin($this->examplePlugin);

        $this->assertNull($settings);
    }

    public function testGetAllMeasurableSettingsShouldReturnOnlyMeasurableSettings()
    {
        $settings = $this->settings->getAllMeasurableSettings($idSite = 1, $idType = null);
        $this->assertArrayHasKey($this->examplePlugin, $settings);

        foreach ($settings as $setting) {
            $this->assertTrue($setting instanceof MeasurableSettings);
        }
    }
}
