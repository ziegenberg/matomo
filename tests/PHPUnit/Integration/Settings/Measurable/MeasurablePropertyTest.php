<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Settings\Plugin;

use Matomo\Db;
use Matomo\Settings\FieldConfig;
use Matomo\Settings\Measurable\MeasurableProperty;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\Mock\Settings\FakeMeasurableSettings;
use Matomo\Tests\Integration\Settings\IntegrationTestCase;

/**
 * @group MeasurableSettings
 * @group Settings
 * @group MeasurableProperty
 */
class MeasurablePropertyTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Db::destroyDatabaseObject();
    }

    protected function createSettingsInstance()
    {
        if (!Fixture::siteCreated(1)) {
            Fixture::createWebsite('2014-01-01 01:01:01');
        }

        return new FakeMeasurableSettings($idSite = 1);
    }

    public function testConstructorShouldNotEstablishADatabaseConnection()
    {
        $this->assertNotDbConnectionCreated();

        new MeasurableProperty('ecommerce', $default = 5, FieldConfig::TYPE_INT, 'MyPlugin', $idSite = 1);

        $this->assertNotDbConnectionCreated();
    }

    public function testConstructorShouldThrowAnExceptionWhenNotAllowedNameIsUsed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Name "name" is not allowed to be used');

        new MeasurableProperty('name', $default = 5, FieldConfig::TYPE_INT, 'MyPlugin', $idSite = 1);
    }
}
