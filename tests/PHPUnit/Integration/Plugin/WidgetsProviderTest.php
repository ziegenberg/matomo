<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Plugin;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\WidgetsProvider;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Widget\WidgetConfig;
use Matomo\Widget\WidgetContainerConfig;

/**
 * @group WidgetsProvider
 * @group WidgetsProviderTest
 */
class WidgetsProviderTest extends IntegrationTestCase
{
    /**
     * @var WidgetsProvider
     */
    private $widgets;

    public function setUp(): void
    {
        parent::setUp();

        $_GET['idSite'] = 1;
        if (!Fixture::siteCreated(1)) {
            Fixture::createWebsite('2015-01-01 00:00:00');
        }

        $this->widgets = new WidgetsProvider(StaticContainer::get('Matomo\Plugin\Manager'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        unset($_GET['idSite']);
    }

    public function testGetWidgetContainerConfigsShouldOnlyFindWidgetContainerConfigs()
    {
        $configs = $this->widgets->getWidgetContainerConfigs();

        $this->assertGreaterThanOrEqual(3, count($configs));

        foreach ($configs as $config) {
            $this->assertTrue($config instanceof WidgetContainerConfig);
        }
    }

    public function testGetWidgetConfigsShouldFindWidgetConfigs()
    {
        $configs = $this->widgets->getWidgetConfigs();

        $this->assertGreaterThanOrEqual(10, count($configs));

        foreach ($configs as $config) {
            $this->assertTrue($config instanceof WidgetConfig);
            $this->assertFalse($config instanceof WidgetContainerConfig);
        }
    }

    public function testGetWidgetConfigsShouldSetModuleAndActionForEachConfig()
    {
        $configs = $this->widgets->getWidgetConfigs();

        foreach ($configs as $config) {
            $this->assertNotEmpty($config->getModule());
            $this->assertNotEmpty($config->getAction());
        }
    }
}
