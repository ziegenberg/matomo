<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Columns;

use Matomo\Columns\Dimension;
use Matomo\Columns\DimensionSegmentFactory;
use Matomo\Plugin\Segment;
use Matomo\Plugins\UserCountry\Columns\Country;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 * @group DimensionSegmentFactory
 */
class DimensionSegmentFactoryTest extends IntegrationTestCase
{
    /** @var  Dimension */
    private $country;

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();

        $this->country = new Country();
    }

    public function tearDown(): void
    {
        Fixture::resetTranslations();
        parent::tearDown();
    }

    private function makeFactory($dimension)
    {
        return new DimensionSegmentFactory($dimension);
    }

    public function testCreateSegment()
    {
        $factory = $this->makeFactory($this->country);
        $segment = $factory->createSegment();

        $this->assertSame('countryCode', $segment->getSegment());
        $this->assertSame('Country', $segment->getName());
        $this->assertSame('UserCountry_VisitLocation', $segment->getCategoryId());
        $this->assertSame(Dimension::TYPE_DIMENSION, $segment->getType());
    }

    public function testCreateSegmentPredefined()
    {
        $factory = $this->makeFactory($this->country);
        $segment = new Segment();
        $segment->setName('My Name');
        $segment->setCategory('My Category');
        $segment = $factory->createSegment($segment);

        $this->assertSame('countryCode', $segment->getSegment());
        $this->assertSame('My Name', $segment->getName());
        $this->assertSame('My Category', $segment->getCategoryId());
        $this->assertSame(Dimension::TYPE_DIMENSION, $segment->getType());
    }
}
