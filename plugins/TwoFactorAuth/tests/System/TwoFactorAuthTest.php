<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TwoFactorAuth\tests\System;

use Matomo\Plugins\TwoFactorAuth\tests\Fixtures\SimpleFixtureTrackFewVisits;
use Matomo\Tests\Framework\TestCase\SystemTestCase;
use Matomo\Container\StaticContainer;
use Matomo\Matomo;
use Matomo\Plugins\TwoFactorAuth\Dao\RecoveryCodeDao;
use Matomo\Plugins\TwoFactorAuth\Dao\TwoFaSecretRandomGenerator;
use Matomo\Plugins\TwoFactorAuth\SystemSettings;
use Matomo\Plugins\TwoFactorAuth\TwoFactorAuthentication;

/**
 * @group TwoFactorAuth
 * @group TwoFactorAuthTest
 * @group Plugins
 */
class TwoFactorAuthTest extends SystemTestCase
{
    /**
     * @var SimpleFixtureTrackFewVisits
     */
    public static $fixture = null; // initialized below class definition

    /**
     * @var RecoveryCodeDao
     */
    private $dao;

    /**
     * @var SystemSettings
     */
    private $settings;

    /**
     * @var TwoFactorAuthentication
     */
    private $twoFa;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao = StaticContainer::get(RecoveryCodeDao::class);
        $this->settings = new SystemSettings();
        $secretGenerator = new TwoFaSecretRandomGenerator();
        $this->twoFa = new TwoFactorAuthentication($this->settings, $this->dao, $secretGenerator);

        self::$fixture->loginAsSuperUser();
    }

    public function testOnRequestDispatchEndNotRequired()
    {
        $this->settings->twoFactorAuthRequired->setValue(true);
        $html = '<html>' . Matomo::getCurrentUserTokenAuth() . '</html>';
        $expected = '<html>' . Matomo::getCurrentUserTokenAuth() . '</html>';
        Matomo::postEvent('Request.dispatch.end', array(&$html, 'module', 'action', array()));
        $this->assertSame($expected, $html);
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoFactorAuthTest::$fixture = new SimpleFixtureTrackFewVisits();
