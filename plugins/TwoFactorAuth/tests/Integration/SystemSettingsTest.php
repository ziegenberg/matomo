<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TwoFactorAuth\tests\Integration;

use Matomo\Plugins\TwoFactorAuth\SystemSettings;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Url;

/**
 * @group TwoFactorAuth
 * @group SystemSettingsTest
 * @group Plugins
 */
class SystemSettingsTest extends IntegrationTestCase
{
    /**
     * @var SystemSettings
     */
    private $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->settings = new SystemSettings();
    }

    public function testTwoFactorAuthRequiredDefaultDisabled()
    {
        $this->assertFalse($this->settings->twoFactorAuthRequired->getValue());
    }

    public function testTwoFactorAuthTitleDefaultTitle()
    {
        $this->assertEquals('Analytics - ' . Url::getCurrentHost(), $this->settings->twoFactorAuthTitle->getValue());
    }
}
