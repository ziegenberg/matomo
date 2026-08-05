<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration;

use Matomo\ArchiveProcessor\PluginsArchiver;
use Matomo\Cache;
use Matomo\EventDispatcher;
use Matomo\Plugin\Archiver;
use Matomo\Tests\Framework\Fixture;
use Matomo\Plugins\VisitsSummary\API as VisitsSummaryAPI;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class ArchiveWithNoVisitsTestMockArchiver extends Archiver
{
    public static $methodsCalled = array();

    public static $runWithoutVisits = false;

    public function aggregateDayReport()
    {
        self::$methodsCalled[] = 'aggregateDayReport';
    }

    public function aggregateMultipleReports()
    {
        self::$methodsCalled[] = 'aggregateMultipleReports';
    }

    public static function shouldRunEvenWhenNoVisits()
    {
        return self::$runWithoutVisits;
    }
}

class ArchiveWithNoVisitsTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Fixture::createWebsite('2011-01-01');

        ArchiveWithNoVisitsTestMockArchiver::$methodsCalled = array();
    }

    public function testsArchivingNotTriggeredWhenNoVisits()
    {
        PluginsArchiver::$archivers['VisitsSummary'] = 'Matomo\Tests\Integration\ArchiveWithNoVisitsTestMockArchiver';

        // initiate archiving w/o adding the event and make sure no methods are called
        VisitsSummaryAPI::getInstance()->get($idSite = 1, 'week', '2012-01-01');

        $this->assertEmpty(ArchiveWithNoVisitsTestMockArchiver::$methodsCalled);
    }

    public function testGetIdSitesToArchiveWhenNoVisitsDoesNotTriggerArchivingIfSiteHasNoVisits()
    {
        // add our mock archiver instance
        // TODO: should use a dummy plugin that is activated for this test explicitly, but that can be tricky, especially in the future

        PluginsArchiver::$archivers['VisitsSummary'] = 'Matomo\Tests\Integration\ArchiveWithNoVisitsTestMockArchiver';

        // mark our only site as should archive when no visits
        $eventDispatcher = $this->getEventDispatcher();
        $eventDispatcher->addObserver('Archiving.getIdSitesToArchiveWhenNoVisits', function (&$idSites) {
            $idSites[] = 1;
        });

        Cache::getTransientCache()->flushAll();

        // initiate archiving and make sure both aggregate methods are called correctly
        VisitsSummaryAPI::getInstance()->get($idSite = 1, 'week', '2012-01-10');

        $expectedMethodCalls = array(
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateDayReport',
            'aggregateMultipleReports',
        );
        $this->assertEquals($expectedMethodCalls, ArchiveWithNoVisitsTestMockArchiver::$methodsCalled);
    }

    public function testPluginArchiverDoesNotTriggerArchivingEvenIfSiteHasNoVisits()
    {
        PluginsArchiver::$archivers['VisitsSummary'] = 'Matomo\Tests\Integration\ArchiveWithNoVisitsTestMockArchiver';

        ArchiveWithNoVisitsTestMockArchiver::$runWithoutVisits = true;

        // initiate archiving and make sure methods are called
        VisitsSummaryAPI::getInstance()->get($idSite = 1, 'week', '2012-01-01');

        $expectedMethodCalls = array();
        $this->assertEquals($expectedMethodCalls, ArchiveWithNoVisitsTestMockArchiver::$methodsCalled);
    }

    /**
     * @return EventDispatcher
     */
    private function getEventDispatcher()
    {
        return self::$fixture->piwikEnvironment->getContainer()->get('Matomo\EventDispatcher');
    }
}
