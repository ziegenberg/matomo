<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Unit;

use Matomo\BaseFactory;

/**
 * @group Core
 */
class FactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreatingExistingClassSucceeds()
    {
        $instance = BaseFactory::factory('Matomo\Timer');

        $this->assertNotNull($instance);
        $this->assertInstanceOf('Matomo\Timer', $instance);
    }

    public function testCreatingInvalidClassThrows()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid class ID');

        BaseFactory::factory("This\\Class\\Does\\Not\\Exist");
    }
}
