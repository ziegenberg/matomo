<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TwoFactorAuth\tests\Integration\Dao;

use Matomo\Plugins\TwoFactorAuth\Dao\TwoFaSecretRandomGenerator;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TwoFactorAuth
 * @group TwoFaSecretRandomGeneratorTest
 * @group Plugins
 */
class TwoFaSecretRandomGeneratorTest extends IntegrationTestCase
{
    /**
     * @var TwoFaSecretRandomGenerator
     */
    private $generator;

    public function setUp(): void
    {
        parent::setUp();

        $this->generator = new TwoFaSecretRandomGenerator();
    }

    public function testGeneratorCodeLength()
    {
        $this->assertSame(16, mb_strlen($this->generator->generateSecret()));
    }

    public function testGeneratorCodeAlwaysDifferent()
    {
        $this->assertNotEquals($this->generator->generateSecret(), $this->generator->generateSecret());
    }
}
