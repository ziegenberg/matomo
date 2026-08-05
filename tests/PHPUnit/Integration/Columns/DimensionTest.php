<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Columns;

// there is a test that requires the class to be defined in a plugin

use Matomo\Columns\Dimension;
use Matomo\Columns\DimensionSegmentFactory;
use Matomo\Plugin\Segment;
use Matomo\Metrics\Formatter;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Plugin\Dimension\ConversionDimension;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Plugin\Manager;
use Matomo\Segment\SegmentsList;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class CustomDimensionTest extends Dimension
{
    protected $columnName  = 'test_dimension';
    protected $columnType  = 'INTEGER (10) DEFAULT 0';
    protected $dbTableName  = 'log_visit';

    public function getId()
    {
        return $this->generateIdFromClass('Matomo\Plugins\Test\Columns\DimensionTest');
    }

    public function hasImplementedEvent($method)
    {
        $method = new \ReflectionMethod($this, $method);
        $declaringClass = $method->getDeclaringClass();

        return 0 === strpos($declaringClass->name, 'Matomo\Tests');
    }

    public function set($param, $value)
    {
        $this->$param = $value;
    }

    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;
    }

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        $segment = new Segment();
        $segment->setSegment('exitPageUrl');
        $segment->setName('Actions_ColumnExitPageURL');
        $segment->setCategory('General_Visit');
        $segmentsList->addSegment($dimensionSegmentFactory->createSegment($segment));

        // custom type and sqlSegment
        $segment = new Segment();
        $segment->setSegment('exitPageUrl');
        $segment->setSqlSegment('customValue');
        $segment->setType(Segment::TYPE_METRIC);
        $segment->setName('Actions_ColumnExitPageURL');
        $segment->setCategory('General_Visit');
        $segmentsList->addSegment($dimensionSegmentFactory->createSegment($segment));
    }
}


/**
 * @group Core
 */
class ColumnDimensionTest extends IntegrationTestCase
{
    /**
     * @var CustomDimensionTest
     */
    private $dimension;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();

        Fixture::createWebsite('2014-04-05 01:02:03');

        Manager::getInstance()->unloadPlugins();
        Manager::getInstance()->doNotLoadAlwaysActivatedPlugins();

        $this->dimension = new CustomDimensionTest();
    }

    public function tearDown(): void
    {
        Fixture::resetTranslations();
        parent::tearDown();
    }

    public function testHasImplementedEventShouldDetectWhetherAMethodWasOverwrittenInTheActualPluginClass()
    {
        $this->assertTrue($this->dimension->hasImplementedEvent('set'));
        $this->assertTrue($this->dimension->hasImplementedEvent('configureSegments'));

        $this->assertFalse($this->dimension->hasImplementedEvent('getSegments'));
    }

    public function testGetColumnNameShouldReturnTheNameOfTheColumn()
    {
        $this->assertSame('test_dimension', $this->dimension->getColumnName());
    }

    public function testHasColumnTypeShouldDetectWhetherAColumnTypeIsSet()
    {
        $this->assertTrue($this->dimension->hasColumnType());

        $this->dimension->set('columnType', '');
        $this->assertFalse($this->dimension->hasColumnType());
    }

    public function testGetNameShouldNotReturnANameByDefault()
    {
        $this->assertSame('', $this->dimension->getName());
    }

    public function testGetAllDimensionsShouldReturnAllKindOfDimensions()
    {
        Manager::getInstance()->loadPlugins(array('Actions', 'Events', 'DevicesDetector', 'Goals', 'CustomVariables'));

        $dimensions = Dimension::getAllDimensions();

        $this->assertGreaterThan(20, count($dimensions));

        $foundConversion = false;
        $foundVisit      = false;
        $foundAction     = false;
        $foundNormal     = false;

        foreach ($dimensions as $dimension) {
            if ($dimension instanceof ConversionDimension) {
                $foundConversion = true;
            } elseif ($dimension instanceof ActionDimension) {
                $foundAction = true;
            } elseif ($dimension instanceof VisitDimension) {
                $foundVisit = true;
            } elseif ($dimension instanceof Dimension) {
                $foundNormal = true;
            } else {
                $this->fail('Unexpected dimension class found');
            }

            if (get_class($dimension) === 'Matomo\Plugins\CustomVariables\CustomDimension') {
                continue;
            }

            $this->assertMatchesRegularExpression('/Piwik.Plugins.(Actions|Events|DevicesDetector|Goals|CustomVariables).Columns/', get_class($dimension));
        }

        $this->assertTrue($foundConversion);
        $this->assertTrue($foundAction);
        $this->assertTrue($foundVisit);
        $this->assertTrue($foundNormal);
    }

    public function testGetDimensionsShouldReturnAllKindOfDimensionsThatBelongToASpecificPlugin()
    {
        Manager::getInstance()->loadPlugins(array('Actions', 'Events', 'DevicesDetector', 'Goals'));

        $dimensions = Dimension::getDimensions(Manager::getInstance()->loadPlugin('Actions'));

        $this->assertGreaterThan(10, count($dimensions));

        $foundVisit      = false;
        $foundAction     = false;

        foreach ($dimensions as $dimension) {
            if ($dimension instanceof ActionDimension) {
                $foundAction = true;
            } elseif ($dimension instanceof VisitDimension) {
                $foundVisit = true;
            }

            $this->assertMatchesRegularExpression('/Piwik.Plugins.Actions.Columns/', get_class($dimension));
        }

        $this->assertTrue($foundAction);
        $this->assertTrue($foundVisit);
    }

    public function testGetDimensionsShouldReturnConversionDimensionsThatBelongToASpecificPlugin()
    {
        Manager::getInstance()->loadPlugins(array('Actions', 'Events', 'DevicesDetector', 'Goals'));

        $dimensions = Dimension::getDimensions(Manager::getInstance()->loadPlugin('Goals'));

        $this->assertGreaterThan(2, count($dimensions));

        $foundConversion = false;

        foreach ($dimensions as $dimension) {
            if ($dimension instanceof ConversionDimension) {
                $foundConversion = true;
            }

            $this->assertMatchesRegularExpression('/Piwik.Plugins.Goals.Columns/', get_class($dimension));
        }

        $this->assertTrue($foundConversion);
    }

    public function testGetSegmentShouldReturnConfiguredSegments()
    {
        $segments = $this->dimension->getSegments();

        $this->assertCount(2, $segments);
        $this->assertInstanceOf('\Matomo\Plugin\Segment', $segments[0]);
        $this->assertInstanceOf('\Matomo\Plugin\Segment', $segments[1]);
    }

    /**
     * @param $expectedType
     * @param $columnType
     * @dataProvider getTypeProvider
     */
    public function testGetTypeShouldGuessTypeBasedOnColumnType($expectedType, $columnType)
    {
        $this->dimension->setColumnType($columnType);
        $this->assertSame($expectedType, $this->dimension->getType());
    }

    public function getTypeProvider()
    {
        return array(
            array($expected = Dimension::TYPE_NUMBER, $columnType = 'INTEGER (10) DEFAULT 0'),
            array($expected = Dimension::TYPE_NUMBER, $columnType = 'INTEGER(10) DEFAULT 0'),
            array($expected = Dimension::TYPE_NUMBER, $columnType = 'INT(10) DEFAULT 0'),
            array($expected = Dimension::TYPE_NUMBER, $columnType = 'int(10) DEFAULT 0'),
            array($expected = Dimension::TYPE_NUMBER, $columnType = 'SMALLINT(10) DEFAULT 0'),
            array($expected = Dimension::TYPE_FLOAT, $columnType = 'FLOAT (10) DEFAULT 0'),
            array($expected = Dimension::TYPE_FLOAT, $columnType = 'DECIMAL(10) DEFAULT 0'),
            array($expected = Dimension::TYPE_BINARY, $columnType = 'BINARY(8)'),
            array($expected = Dimension::TYPE_TIMESTAMP, $columnType = 'timestamp null'),
            array($expected = Dimension::TYPE_TIMESTAMP, $columnType = 'timeStAmp null'),
            array($expected = Dimension::TYPE_DATETIME, $columnType = 'DATETIME NOT NULL'),
            array($expected = Dimension::TYPE_DATE, $columnType = 'DATE NOT NULL'),
            array($expected = Dimension::TYPE_TEXT, $columnType = ''),
        );
    }

    public function testAddSegmentShouldPrefilSomeSegmentValuesIfNotDefinedYetAndGuessTypeMetric()
    {
        $segments = $this->dimension->getSegments();

        $this->assertEquals(Segment::TYPE_METRIC, $segments[0]->getType());
    }

    public function testAddSegmentShouldPrefilSomeSegmentValuesIfNotDefinedYetAndGuessTypeDimension()
    {
        $this->dimension->setColumnType('TEXT NOT NULL');
        $segments = $this->dimension->getSegments();

        $this->assertEquals(Segment::TYPE_DIMENSION, $segments[0]->getType());
    }

    public function testAddSegmentShouldNotOverwritePreAssignedValues()
    {
        $segments = $this->dimension->getSegments();

        $this->assertEquals(Segment::TYPE_METRIC, $segments[1]->getType());
    }

    public function testGetIdShouldCorrectlyGenerateIdFromDimensionsQualifiedClassName()
    {
        $this->assertEquals("Test.DimensionTest", $this->dimension->getId());
    }


    /**
     * @dataProvider getFormatValueProvider
     */
    public function testFormatValue($type, $value, $expected)
    {
        $formatter = new Formatter();
        $this->dimension->setType($type);
        $formatted = $this->dimension->formatValue($value, $idSite = 1, $formatter);

        $this->assertEquals($expected, $formatted);
    }

    public function getFormatValueProvider()
    {
        return array(
            array($type = Dimension::TYPE_NUMBER, $value = 5.354, $expected = 5),
            array($type = Dimension::TYPE_FLOAT, $value = 5.354, $expected = 5.35),
            array($type = Dimension::TYPE_MONEY, $value = 5.392, $expected = '$5.39'),
            array($type = Dimension::TYPE_PERCENT, $value = 0.343, $expected = '34.3%'),
            array($type = Dimension::TYPE_DURATION_S, $value = 121, $expected = '00:02:01'),
            array($type = Dimension::TYPE_DURATION_MS, $value = 392, $expected = '0.39s'),
            array($type = Dimension::TYPE_BYTE, $value = 3912, $expected = '3.8 K'),
            array($type = Dimension::TYPE_BOOL, $value = 0, $expected = 'No'),
            array($type = Dimension::TYPE_BOOL, $value = 1, $expected = 'Yes'),
        );
    }

    protected static $availableColumnDimensions = [
        'Matomo\Plugins\Actions\Columns\EntryPageTitle',
        'Matomo\Plugins\Actions\Columns\EntryPageUrl',
        'Matomo\Plugins\Actions\Columns\ExitPageTitle',
        'Matomo\Plugins\Actions\Columns\ExitPageUrl',
        'Matomo\Plugins\Actions\Columns\IdPageview',
        'Matomo\Plugins\Actions\Columns\PageTitle',
        'Matomo\Plugins\Actions\Columns\PageUrl',
        'Matomo\Plugins\Actions\Columns\SearchCategory',
        'Matomo\Plugins\Actions\Columns\SearchCount',
        'Matomo\Plugins\Actions\Columns\TimeSpentRefAction',
        'Matomo\Plugins\Actions\Columns\VisitTotalActions',
        'Matomo\Plugins\Actions\Columns\VisitTotalInteractions',
        'Matomo\Plugins\Actions\Columns\VisitTotalSearches',
        'Matomo\Plugins\AIAgents\Columns\AIAgentName',
        'Matomo\Plugins\Bandwidth\Columns\Bandwidth',
        'Matomo\Plugins\Contents\Columns\ContentInteraction',
        'Matomo\Plugins\Contents\Columns\ContentName',
        'Matomo\Plugins\Contents\Columns\ContentPiece',
        'Matomo\Plugins\Contents\Columns\ContentTarget',
        'Matomo\Plugins\CoreHome\Columns\Profilable',
        'Matomo\Plugins\CoreHome\Columns\ServerTime',
        'Matomo\Plugins\CoreHome\Columns\UserId',
        'Matomo\Plugins\CoreHome\Columns\VisitFirstActionTime',
        'Matomo\Plugins\CoreHome\Columns\VisitGoalBuyer',
        'Matomo\Plugins\CoreHome\Columns\VisitGoalConverted',
        'Matomo\Plugins\CoreHome\Columns\VisitTotalTime',
        'Matomo\Plugins\CoreHome\Columns\VisitorReturning',
        'Matomo\Plugins\CoreHome\Columns\VisitorSecondsSinceFirst',
        'Matomo\Plugins\CoreHome\Columns\VisitorSecondsSinceOrder',
        'Matomo\Plugins\CoreHome\Columns\VisitsCount',
        'Matomo\Plugins\DevicePlugins\Columns\PluginCookie',
        'Matomo\Plugins\DevicePlugins\Columns\PluginFlash',
        'Matomo\Plugins\DevicePlugins\Columns\PluginJava',
        'Matomo\Plugins\DevicePlugins\Columns\PluginPdf',
        'Matomo\Plugins\DevicePlugins\Columns\PluginQuickTime',
        'Matomo\Plugins\DevicePlugins\Columns\PluginRealPlayer',
        'Matomo\Plugins\DevicePlugins\Columns\PluginSilverlight',
        'Matomo\Plugins\DevicePlugins\Columns\PluginWindowsMedia',
        'Matomo\Plugins\DevicesDetection\Columns\BrowserEngine',
        'Matomo\Plugins\DevicesDetection\Columns\BrowserName',
        'Matomo\Plugins\DevicesDetection\Columns\BrowserVersion',
        'Matomo\Plugins\DevicesDetection\Columns\ClientType',
        'Matomo\Plugins\DevicesDetection\Columns\DeviceBrand',
        'Matomo\Plugins\DevicesDetection\Columns\DeviceModel',
        'Matomo\Plugins\DevicesDetection\Columns\DeviceType',
        'Matomo\Plugins\DevicesDetection\Columns\Os',
        'Matomo\Plugins\DevicesDetection\Columns\OsVersion',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewCategory',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewCategory2',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewCategory3',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewCategory4',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewCategory5',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewName',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewPrice',
        'Matomo\Plugins\Ecommerce\Columns\ProductViewSku',
        'Matomo\Plugins\Ecommerce\Columns\Revenue',
        'Matomo\Plugins\Events\Columns\EventAction',
        'Matomo\Plugins\Events\Columns\EventCategory',
        'Matomo\Plugins\Events\Columns\TotalEvents',
        'Matomo\Plugins\Goals\Columns\PageviewsBefore',
        'Matomo\Plugins\PagePerformance\Columns\TimeDomCompletion',
        'Matomo\Plugins\PagePerformance\Columns\TimeDomProcessing',
        'Matomo\Plugins\PagePerformance\Columns\TimeNetwork',
        'Matomo\Plugins\PagePerformance\Columns\TimeOnLoad',
        'Matomo\Plugins\PagePerformance\Columns\TimeServer',
        'Matomo\Plugins\PagePerformance\Columns\TimeTransfer',
        'Matomo\Plugins\Referrers\Columns\Keyword',
        'Matomo\Plugins\Referrers\Columns\ReferrerName',
        'Matomo\Plugins\Referrers\Columns\ReferrerType',
        'Matomo\Plugins\Referrers\Columns\ReferrerUrl',
        'Matomo\Plugins\Resolution\Columns\Resolution',
        'Matomo\Plugins\UserCountry\Columns\City',
        'Matomo\Plugins\UserCountry\Columns\Country',
        'Matomo\Plugins\UserCountry\Columns\Latitude',
        'Matomo\Plugins\UserCountry\Columns\Longitude',
        'Matomo\Plugins\UserCountry\Columns\Region',
        'Matomo\Plugins\UserLanguage\Columns\Language',
        'Matomo\Plugins\VisitTime\Columns\LocalTime',
        'Matomo\Plugins\VisitorInterest\Columns\VisitorSecondsSinceLast',
    ];

    /**
     * Check all available dimensions are listed above
     */
    public function testNoNewDimensionsAvailable()
    {
        self::expectNotToPerformAssertions();
        Manager::getInstance()->loadAllPluginsAndGetTheirInfo();

        $dimensions = Dimension::getAllDimensions();

        foreach ($dimensions as $dimension) {
            if (!$dimension->getColumnName() || !$dimension->getVersion()) {
                continue; // ignore dimensions that don't manage their database column
            }

            if (!in_array(get_class($dimension), self::$availableColumnDimensions)) {
                $this->fail("New dimension found: " . get_class($dimension) . "\nPlease update list of available column dimensions");
            }
        }
    }

    /**
     * Check all dimensions listed above, still exist and manage their column
     */
    public function testNoDimensionWasRemoved()
    {
        self::expectNotToPerformAssertions();
        Manager::getInstance()->loadAllPluginsAndGetTheirInfo();

        $removedDimensions = Dimension::getRemovedDimensions();

        foreach (self::$availableColumnDimensions as $dimension) {
            if (!class_exists($dimension)) {
                $this->fail("Dimension does no longer exist: $dimension\nPlease update list of available column dimensions and don't forget to add dimension to Dimension::getRemovedDimensions()");
            }

            $dimensionObj = new $dimension();

            if (!$dimensionObj->getColumnName() || !$dimensionObj->getVersion()) {
                $this->fail("Dimension does no longer manage a column: $dimension\nPlease remove it from the list of available column dimensions");
            }

            if (in_array($dimension, $removedDimensions)) {
                $this->fail("Dimension listed as available found in list of removed dimensions: $dimension");
            }
        }
    }

    /**
     * Check non of the dimensions marked as removed still exist
     */
    public function testRemovedDimensionNoLongerExists()
    {
        Manager::getInstance()->loadAllPluginsAndGetTheirInfo();

        $removedDimensions = Dimension::getRemovedDimensions();

        foreach ($removedDimensions as $removedDimension) {
            $this->assertFalse(class_exists($removedDimension), "Dimension marked as removed but still exist: $removedDimension");
        }
    }

    public function testGroupValue()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(800.0, $this->dimension->groupValue(800, 1));
    }

    public function testGroupValueStringValue()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(800.0, $this->dimension->groupValue('800', 1));
    }

    public function testGroupValueLargerValue()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(80000000.0, $this->dimension->groupValue(80000000, 1));
    }

    public function testGroupValueLargerStringValue()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(80000000.0, $this->dimension->groupValue('80000000', 1));
    }

    public function testGroupValueLargerValueWithDecimal()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(80000000.0, $this->dimension->groupValue(80000000.123, 1));
    }

    public function testGroupValueLargerStringValueWithDecimal()
    {
        $this->dimension->setType(Dimension::TYPE_DURATION_MS);
        $this->assertSame(80000000.0, $this->dimension->groupValue('80000000.123', 1));
    }
}
