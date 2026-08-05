<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API\tests\System;

use Matomo\Cache;
use Matomo\Plugins\SitesManager\tests\Fixtures\ManySites;
use Matomo\Policy\CnilPolicy;
use Matomo\Policy\PolicyManager;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group API
 * @group ApiTest
 * @group Plugins
 */
class ApiTest extends SystemTestCase
{
    /**
     * @var ManySites
     */
    public static $fixture = null; // initialized below class definition

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetSegmentsMetadataWithComplianceAvailable(): void
    {
        $this->runApiTests('API.getSegmentsMetadata', [
            'testSuffix' => '_compliancePolicyFeatureFlagEnabled',
            'otherRequestParameters' => [
                'idSite' => '1',
            ],
        ]);
    }

    public function testGetSegmentsMetadataWhenPolicyEnforced(): void
    {
        Cache::getTransientCache()->flushAll();
        PolicyManager::setPolicyActiveStatus(CnilPolicy::class, true);

        $this->runApiTests('API.getSegmentsMetadata', [
            'testSuffix' => '_compliancePolicyEnforced',
            'otherRequestParameters' => [
                'idSite' => '1',
            ],
        ]);

        PolicyManager::setPolicyActiveStatus(CnilPolicy::class, false);
    }

    public static function getPathToTestDirectory()
    {
        return __DIR__;
    }
}

ApiTest::$fixture = new ManySites();
