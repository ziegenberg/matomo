<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomJsTracker\tests\Integration;

use Matomo\Plugins\CustomJsTracker\API;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\Mock\FakeAccess;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomJsTracker
 * @group ApiTest
 * @group Api
 * @group Plugins
 */
class ApiTest extends IntegrationTestCase
{
    /**
     * @var API
     */
    private $api;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        Fixture::createWebsite('2014-01-01 01:02:03');
        $this->api = API::getInstance();
    }

    public function testDoesIncludePluginTrackersAutomaticallyFailsIfNotEnoughPermission()
    {
        $this->expectException(\Matomo\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSomeAdminAccess');

        $this->setUser();
        $this->api->doesIncludePluginTrackersAutomatically();
    }

    public function testDoesIncludePluginTrackersAutomaticallyFailsIfNotEnoughPermissionAnonymous()
    {
        $this->expectException(\Matomo\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSomeAdminAccess');

        $this->setAnonymousUser();
        $this->api->doesIncludePluginTrackersAutomatically();
    }

    public function testDoesIncludePluginTrackersAutomaticallyReturnsValueWhenEnoughPermission()
    {
        $this->assertTrue($this->api->doesIncludePluginTrackersAutomatically());
    }

    protected function setUser()
    {
        FakeAccess::clearAccess(false);
        FakeAccess::$identity = 'testUsername';
        FakeAccess::$idSitesView = array(1);
        FakeAccess::$idSitesAdmin = array();
    }

    protected function setAnonymousUser()
    {
        FakeAccess::clearAccess();
        FakeAccess::$identity = 'anonymous';
    }

    public function provideContainerConfig()
    {
        return array(
            'Matomo\Access' => new FakeAccess(),
        );
    }
}
