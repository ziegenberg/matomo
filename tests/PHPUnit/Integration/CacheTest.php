<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration;

use Matomo\Cache;
use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Cache
 */
class CacheTest extends IntegrationTestCase
{
    public function testGetEagerCacheShouldPersistOnceEventWasTriggered()
    {
        $storageId = 'eagercache-test-ui';
        $cache = Cache::getEagerCache();
        $cache->save('test', 'mycontent'); // make sure something was changed, otherwise it won't save anything

        /** @var Cache\Backend $backend */
        $backend = StaticContainer::get('Matomo\Cache\Backend');
        $this->assertFalse($backend->doContains($storageId));

        $result = '';
        $module = 'CoreHome';
        $action = 'index';
        $params = array();
        Matomo::postEvent('Request.dispatch.end', array(&$result, $module, $action, $params)); // should trigger save

        $this->assertTrue($backend->doContains($storageId));
    }
}
