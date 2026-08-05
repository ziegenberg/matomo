<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome\tests\Integration;

use Matomo\Archive\ArchivePurger;
use Matomo\ArchiveProcessor\Rules;
use Matomo\Common;
use Matomo\Date;
use Matomo\Db;
use Matomo\Mail;
use Matomo\Plugins\CoreAdminHome\Emails\JsTrackingCodeMissingEmail;
use Matomo\Plugins\CoreAdminHome\Emails\TrackingFailuresEmail;
use Matomo\Plugins\CoreAdminHome\Tasks;
use Matomo\Plugins\CoreAdminHome\Tasks\ArchivesToPurgeDistributedList;
use Matomo\Scheduler\Task;
use Matomo\Tests\Fixtures\RawArchiveDataWithTempAndInvalidated;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Tracker\Failures;
use Matomo\Tracker\Request;
use Matomo\Log\NullLogger;

/**
 * @group Core
 */
class TasksTest extends IntegrationTestCase
{
    /**
     * @var RawArchiveDataWithTempAndInvalidated
     */
    public static $fixture;

    /**
     * @var Tasks
     */
    private $tasks;

    /**
     * @var Date
     */
    private $january;

    /**
     * @var Date
     */
    private $february;

    /**
     * @var Mail
     */
    private $mail;

    public function setUp(): void
    {
        parent::setUp();

        self::$fixture->loginAsSuperUser();

        $this->january = Date::factory('2015-01-01');
        $this->february = Date::factory('2015-02-01');

        $archivePurger = new ArchivePurger();
        $archivePurger->setTodayDate(Date::factory('2015-02-27'));
        $archivePurger->setYesterdayDate(Date::factory('2015-02-26'));
        $archivePurger->setNow(Date::factory('2015-02-27 08:00:00')->getTimestamp());

        $this->tasks = new Tasks($archivePurger, new NullLogger(), new Failures());

        $this->mail = null;
    }

    public function tearDown(): void
    {
        Rules::$disablePureOutdatedArchive = false;

        parent::tearDown();
    }

    public function testPurgeInvalidatedArchivesPurgesCorrectInvalidatedArchivesAndOnlyPurgesDataForDatesAndSitesInInvalidatedReportsDistributedList()
    {
        $this->setUpInvalidatedReportsDistributedList($dates = array($this->february));

        $this->tasks->purgeInvalidatedArchives();

        self::$fixture->assertInvalidatedArchivesPurged($this->february);
        self::$fixture->assertInvalidatedArchivesNotPurged($this->january);

        // assert invalidated reports distributed list has changed
        $archivesToPurgeDistributedList = new ArchivesToPurgeDistributedList();
        $yearMonths = $archivesToPurgeDistributedList->getAll();

        $this->assertEmpty($yearMonths);
    }

    public function testPurgeOutdatedArchivesSkipsPurgingWhenBrowserArchivingDisabledAndCronArchiveTriggerNotPresent()
    {
        Rules::setBrowserTriggerArchiving(false);
        $wasPurged = $this->tasks->purgeOutdatedArchives();
        $this->assertFalse($wasPurged);
    }

    public function testPurgeOutdatedArchivesPurgesWhenBrowserArchivingEnabledAndCronArchiveTriggerPresent()
    {
        Rules::setBrowserTriggerArchiving(false);
        Rules::$disablePureOutdatedArchive = true;

        $wasPurged = $this->tasks->purgeOutdatedArchives();
        $this->assertTrue($wasPurged);
    }

    public function testScheduleAddsRightAmountOfTasks()
    {
        Fixture::createWebsite('2012-01-01 00:00:00');
        Fixture::createWebsite(Date::now()->subDay(5)->getDatetime());
        Fixture::createWebsite(Date::now()->subDay(2)->getDatetime());
        Fixture::createWebsite(Date::now()->subDay(4)->getDatetime());
        Fixture::createWebsite(Date::now()->getDatetime());

        $this->tasks->schedule();

        $tasks = $this->tasks->getScheduledTasks();
        $tasks = array_map(function (Task $task) {
            return $task->getMethodName() . '.' . $task->getMethodParameter();
        }, $tasks);

        $expected = [
            'invalidateOutdatedArchives.',
            'deleteOldFingerprintSalts.',
            'purgeOutdatedArchives.',
            'purgeInvalidatedArchives.',
            'purgeBrokenArchivesCurrentMonth.',
            'purgeInvalidationsForDeletedSites.',
            'purgeOrphanedArchives.',
            'optimizeArchiveTable.',
            'cleanupTrackingFailures.',
            'notifyTrackingFailures.',
            'updateSpammerList.',
            'checkSiteHasTrackedVisits.2',
            'checkSiteHasTrackedVisits.3',
            'checkSiteHasTrackedVisits.4',
            'checkSiteHasTrackedVisits.5',
        ];
        $this->assertEquals($expected, $tasks);
    }

    public function testCheckSiteHasTrackedVisitsDoesNothingIfTheSiteHasVisits()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');

        $tracker = Fixture::getTracker($idSite, '2014-02-02 03:04:05');
        Fixture::checkResponse($tracker->doTrackPageView('alskdjfs'));

        $this->assertEquals(1, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertEmpty($this->mail);
    }

    public function testCheckSiteHasTrackedVisitsDoesNothingIfSiteHasNoCreationUser()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');
        Db::query("UPDATE " . Common::prefixTable('site') . ' SET creator_login = NULL WHERE idsite = ' . $idSite . ';');

        $this->assertEquals(0, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertEmpty($this->mail);
    }

    public function testCheckSitesHasTrackedVisitsSendsJsCodeMissingEmailIfSiteHasNoVisitsAndCreationUser()
    {
        $idSite = Fixture::createWebsite('2012-01-01 00:00:00');

        $this->assertEquals(0, Db::fetchOne("SELECT COUNT(*) FROM " . Common::prefixTable('log_visit')));

        $this->tasks->checkSiteHasTrackedVisits($idSite);

        $this->assertInstanceOf(JsTrackingCodeMissingEmail::class, $this->mail);

        /** @var JsTrackingCodeMissingEmail $mail */
        $mail = $this->mail;
        $this->assertEquals($mail->getLogin(), 'superUserLogin');
        $this->assertEquals($mail->getEmailAddress(), 'hello@example.org');
        $this->assertEquals($mail->getIdSite(), $idSite);
    }

    public function testCleanupTrackingFailuresDoesNotCauseAnyException()
    {
        self::expectNotToPerformAssertions();

        // it is only calling one method which is already tested... no need to write complex tests for it
        $this->tasks->cleanupTrackingFailures();
    }

    public function testNotifyTrackingFailuresDoesNotSendAnyMailWhenThereAreNoTrackingRequests()
    {
        $this->tasks->notifyTrackingFailures();
        $this->assertNull($this->mail);
    }

    public function testNotifyTrackingFailuresSendsMailWhenThereAreTrackingFailures()
    {
        $failures = new Failures();
        $failures->logFailure(1, new Request(array('idsite' => 9999, 'rec' => 1)));
        $failures->logFailure(1, new Request(array('idsite' => 9998, 'rec' => 1)));
        Fixture::createSuperUser(false);
        $this->tasks->notifyTrackingFailures();

        /** @var TrackingFailuresEmail $mail */
        $mail = $this->mail;
        $this->assertInstanceOf(TrackingFailuresEmail::class, $mail);
        $this->assertEquals('superUserLogin', $mail->getLogin());
        $this->assertEquals('hello@example.org', $mail->getEmailAddress());
        $this->assertEquals(2, $mail->getNumFailures());
    }

    /**
     * @param Date[] $dates
     */
    private function setUpInvalidatedReportsDistributedList($dates)
    {
        $yearMonths = array();
        foreach ($dates as $date) {
            $yearMonths[] = $date->toString('Y_m');
        }

        $archivesToPurgeDistributedList = new ArchivesToPurgeDistributedList();
        $archivesToPurgeDistributedList->add($yearMonths);
    }

    public function provideContainerConfig()
    {
        return [
            'observers.global' => \Matomo\DI::add([
                ['Mail.send', \Matomo\DI::value(function (Mail $mail) {
                    $this->mail = $mail;
                })],
            ]),
        ];
    }

    /**
     * @param Fixture $fixture
     */
    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = true;
    }
}

TasksTest::$fixture = new RawArchiveDataWithTempAndInvalidated();
