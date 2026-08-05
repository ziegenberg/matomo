<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\System;

use Matomo\ArchiveProcessor\Parameters;
use Matomo\Config;
use Matomo\Matomo;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Fixtures\ThreeSitesWithSharedVisitors;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group Core
 * @group MultipleSitesArchivingTest
 */
class MultipleSitesArchivingTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $extraSite = Fixture::createWebsite(self::$fixture->dateTime, $ecommerce = 1, "the site");

        Matomo::addAction("ArchiveProcessor.Parameters.getIdSites", function (&$sites, $period) use ($extraSite) {
            if (reset($sites) == $extraSite) {
                $sites = array(1, 2, 3);
            }
        });

        Matomo::addAction('CronArchive.getIdSitesNotUsingTracker', function (&$idSitesNotUsingTradker) use ($extraSite) {
            $idSitesNotUsingTradker[] = $extraSite;
        });

        Matomo::addAction('ArchiveProcessor.shouldAggregateFromRawData', function (&$shouldAggregateFromRawData, Parameters $params) {
            if ($params->getSite()->getId() == 4) {
                $shouldAggregateFromRawData = true;
            }
        });

        Config::getInstance()->General['enable_processing_unique_visitors_multiple_sites'] = 1;
        Config::getInstance()->Tracker['enable_fingerprinting_across_websites'] = 1;
    }

    public function getApiForTesting()
    {
        $dateTime = self::$fixture->dateTime;

        return array(
            array('VisitsSummary.get', array('idSite' => 4,
                                             'date' => $dateTime,
                                             'periods' => array('day', 'month'),
                                             'testSuffix' => '_sitesGroup')),
        );
    }

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }
}

MultipleSitesArchivingTest::$fixture = new ThreeSitesWithSharedVisitors();
