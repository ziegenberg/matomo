<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Columns;

use Matomo\Columns\DimensionsProvider;
use Matomo\Plugin\Manager;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 */
class DimensionsProviderTest extends IntegrationTestCase
{
    /**
     * @var DimensionsProvider
     */
    private $provider;

    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new DimensionsProvider();
    }

    public function testFactory()
    {
        Manager::getInstance()->loadPlugins(array('ExampleTracker'));
        $dimension = $this->provider->factory("ExampleTracker.ExampleDimension");
        $this->assertInstanceOf('Matomo\Plugins\ExampleTracker\Columns\ExampleDimension', $dimension);
    }
}
