<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Measurable;

use Matomo\Settings\FieldConfig;
use Matomo\Settings\Measurable\MeasurableSetting;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\Mock\FakeAccess;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class MeasurableSettingTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Fixture::createWebsite('2014-01-01 00:00:01');
        FakeAccess::$superUser = true;
    }

    private function createSetting()
    {
        $setting = new MeasurableSetting('name', $default = '', FieldConfig::TYPE_STRING, 'Plugin', $idSite = 1);
        return $setting;
    }

    public function testSetValueGetValueShouldSucceedIfEnoughPermission()
    {
        $setting = $this->createSetting();
        $setting->setValue('test');
        $value = $setting->getValue();

        $this->assertSame('test', $value);
    }

    public function testSetValueShouldThrowExceptionIfOnlyViewPermission()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoreAdminHome_PluginSettingChangeNotAllowed');

        FakeAccess::clearAccess();
        FakeAccess::setIdSitesView(array(1, 2, 3));
        $this->createSetting()->setValue('test');
    }

    public function testSetValueShouldThrowExceptionIfNoPermissionAtAll()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CoreAdminHome_PluginSettingChangeNotAllowed');

        FakeAccess::clearAccess();
        $this->createSetting()->setValue('test');
    }

    public function provideContainerConfig()
    {
        return array(
            'Matomo\Access' => new FakeAccess(),
        );
    }
}
