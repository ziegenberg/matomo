<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager\tests\Integration;

use Matomo\Config;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\UserPreferences;
use Matomo\Plugins\UsersManager\API as APIUsersManager;
use Matomo\Plugins\SitesManager\API as SitesManagerAPI;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\Mock\FakeAccess;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group UsersManager
 * @group UserPreferencesTest
 * @group Plugins
 * @group Plugins
 */
class UserPreferencesTest extends IntegrationTestCase
{
    /**
     * @var UserPreferences
     */
    private $userPreferences;

    public function setUp(): void
    {
        parent::setUp();

        $this->userPreferences = new UserPreferences();

        $this->setSuperUser();

        $identity = FakeAccess::$identity;
        FakeAccess::$identity = 'foo'; // avoids error user already exists when it doesn't
        APIUsersManager::getInstance()->addUser($identity, '22111214k4,mdw<L', 'foo@example.com');
        FakeAccess::$identity = $identity;
    }

    public function testGetDefaultReportWhenLoginNotExists()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage('User does not exist');

        APIUsersManager::getInstance()->setUserPreference(
            'foo',
            APIUsersManager::PREFERENCE_DEFAULT_REPORT,
            '1'
        );
    }

    public function testGetDefaultReportWhenWrongPreference()
    {
        self::expectException(\Exception::class);
        self::expectExceptionMessage('Not supported preference name');

        APIUsersManager::getInstance()->setUserPreference(
            Matomo::getCurrentUserLogin(),
            'foo',
            '1'
        );
    }

    public function testGetDefaultReportShouldReturnFalseByDefault()
    {
        $this->assertEquals(false, $this->userPreferences->getDefaultReport());
    }

    public function testGetDefaultReportShouldReturnTheRawValueIfNotNumeric()
    {
        $this->setDefaultReport('MultiSites');
        $this->assertEquals('MultiSites', $this->userPreferences->getDefaultReport());
    }

    public function testGetDefaultReportShouldNotReturnSiteIdIfNoPermissionForSite()
    {
        $this->createSite();
        $this->setDefaultReport(1);
        $this->setAnonymous();
        $this->assertEquals(false, $this->userPreferences->getDefaultReport());
    }

    public function testGetDefaultReportShouldReturnSiteIdIfPermissionForSite()
    {
        $this->createSite();
        $this->setDefaultReport(1);
        $this->assertEquals(1, $this->userPreferences->getDefaultReport());
    }

    public function testGetDefaultReportShouldReturnFalseWhenSavedSiteNoLongerExistsForSuperUser()
    {
        $this->createSite();
        $this->createSite();
        $this->setDefaultReport(1);

        SitesManagerAPI::getInstance()->deleteSite(1);
        $this->setDefaultReport(1);

        $this->assertFalse($this->userPreferences->getDefaultReport());
    }

    public function testGetDefaultWebsiteIdShouldReturnFalseByDefault()
    {
        $this->assertEquals(false, $this->userPreferences->getDefaultWebsiteId());
    }

    public function testGetDefaultWebsiteIdShouldReturnASiteIfOneExistsAndHasAccess()
    {
        $this->createSite();
        $this->assertEquals(1, $this->userPreferences->getDefaultWebsiteId());
    }

    public function testGetDefaultWebsiteIdShouldReturnFalseIfASiteExistsButHasNoAccess()
    {
        $this->createSite();
        $this->setAnonymous();
        $this->assertEquals(false, $this->userPreferences->getDefaultWebsiteId());
    }

    public function testGetDefaultWebsiteIdShouldReturnASiteEvenIfMultiSitesIsDefaultReport()
    {
        $this->setDefaultReport('MultiSites');
        $this->createSite();
        $this->assertEquals(1, $this->userPreferences->getDefaultWebsiteId());
    }

    /**
     * @dataProvider provideDefaultDates
     */
    public function testGetDefaultDateAndPeriod($defaultDate, $expectedDate, $expectedPeriod)
    {
        $this->setDefaultDate($defaultDate);
        $this->assertEquals($expectedDate, $this->userPreferences->getDefaultDate());
        $this->assertEquals($expectedPeriod, $this->userPreferences->getDefaultPeriod());
    }

    public function provideDefaultDates()
    {
        return array(
            'today'     => array('today', 'today', 'day'),
            'yesterday' => array('yesterday', 'yesterday', 'day'),
            'month'     => array('month', 'today', 'month'),
            'week'      => array('week', 'today', 'week'),
            'last7'     => array('last7', 'last7', 'range'),
            'last30'    => array('last30', 'last30', 'range'),
        );
    }

    public function testGetDefaultPeriodShouldOnlyReturnAllowedPeriods()
    {
        // Only allow for week period
        Config::getInstance()->General['enabled_periods_UI'] = 'week';
        Config::getInstance()->General['default_period'] = 'week';
        Config::getInstance()->General['default_day'] = 'yesterday';

        $this->setDefaultDate('today');
        // Should be system defaults
        $this->assertEquals('week', $this->userPreferences->getDefaultPeriod());
        $this->assertEquals('yesterday', $this->userPreferences->getDefaultDate());
    }

    public function testGetDefaultDateShouldOnlyReturnDateInAllowedPeriods()
    {
        // Only allow for week period
        Config::getInstance()->General['enabled_periods_UI'] = 'day';
        Config::getInstance()->General['default_period'] = 'day';
        $this->setDefaultDate('last7');
        $this->assertEquals('yesterday', $this->userPreferences->getDefaultDate());
    }

    private function setSuperUser()
    {
        FakeAccess::$superUser = true;
    }

    private function setAnonymous()
    {
        FakeAccess::clearAccess();
    }

    private function createSite()
    {
        Fixture::createWebsite('2013-01-23 01:23:45');
    }

    private function setDefaultReport($defaultReport)
    {
        APIUsersManager::getInstance()->setUserPreference(
            Matomo::getCurrentUserLogin(),
            APIUsersManager::PREFERENCE_DEFAULT_REPORT,
            $defaultReport
        );
    }

    private function setDefaultDate($date)
    {
        APIUsersManager::getInstance()->setUserPreference(
            Matomo::getCurrentUserLogin(),
            APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE,
            $date
        );
    }

    public function provideContainerConfig()
    {
        return array(
            'Matomo\Access' => new FakeAccess(),
        );
    }
}
