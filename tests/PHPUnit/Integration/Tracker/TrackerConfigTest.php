<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Tracker;

use Matomo\Config;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Tracker\TrackerConfig;

/**
 * @group Core
 * @group TrackerConfigTest
 */
class TrackerConfigTest extends IntegrationTestCase
{
    public function testGetConfigValueWithUseThirdPartyIdCookieReturnsResult(): void
    {
        $this->assertEquals(false, TrackerConfig::getConfigValue('use_third_party_id_cookie'));
    }

    public function testGetBooleanConfigValueWithUseThirdPartyIdCookieReturnsResult(): void
    {
        Config::getInstance()->Tracker = ['use_third_party_id_cookie' => true];
        Config::getInstance()->Tracker_10 = ['use_third_party_id_cookie' => false];

        $this->assertTrue(TrackerConfig::getBoolConfigValue('use_third_party_id_cookie'));
        $this->assertFalse(TrackerConfig::getBoolConfigValue('use_third_party_id_cookie', null, 10));
    }
}
