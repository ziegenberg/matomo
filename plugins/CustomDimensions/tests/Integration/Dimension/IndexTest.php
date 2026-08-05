<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDimensions\tests\Integration\Dimension;

use Matomo\Plugins\CustomDimensions\CustomDimensions;
use Matomo\Plugins\CustomDimensions\Dao\Configuration;
use Matomo\Plugins\CustomDimensions\Dimension\Index;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomDimensions
 * @group IndexTest
 * @group Index
 * @group Dao
 * @group Plugins
 */
class IndexTest extends IntegrationTestCase
{
    /**
     * @var Index
     */
    private $index;

    public function setUp(): void
    {
        parent::setUp();

        if (!Fixture::siteCreated(1)) {
            Fixture::createWebsite('2014-01-01 00:00:00');
        }

        $this->index = new Index();
    }

    public function testShouldReturn1WhenNoIndexIsUsedYet()
    {
        $this->assertSame(1, $this->index->getNextIndex($idSite = 1, CustomDimensions::SCOPE_ACTION));
    }

    public function testShouldNotActuallyCreateAnIndexOnlyReturnNextFreeIndex()
    {
        $idSite = 1;
        $scope = CustomDimensions::SCOPE_ACTION;

        $this->assertSame(1, $this->index->getNextIndex($idSite, $scope));
        $this->assertSame(1, $this->index->getNextIndex($idSite, $scope));
    }

    public function testShouldReturnNextFreeIndex()
    {
        $idSite = 1;

        $this->createIndex($idSite, CustomDimensions::SCOPE_ACTION, $index = 1);
        $this->assertSame(2, $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_ACTION));

        $this->createIndex($idSite, CustomDimensions::SCOPE_VISIT, $index = 1);
        $this->assertSame(2, $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_VISIT));

        $this->createIndex($idSite, CustomDimensions::SCOPE_VISIT, $index = 2);
        $this->assertSame(3, $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_VISIT));
        $this->assertSame(2, $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_ACTION)); // should remain unchanged
    }

    public function testShouldThrowAnExceptionIfAllIndexesAreUsed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All Custom Dimensions for website 1 in scope \'action\' are already used');

        $idSite = 1;

        foreach (range(1, 5) as $index) {
            $this->createIndex($idSite, CustomDimensions::SCOPE_ACTION, $index);
            // all indexes are in use after this
        }

        // should be still possible to acquire an index for different scope
        $this->assertSame(1, $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_VISIT));
        // should be still possible to acquire for different website in scope action
        $this->assertSame(1, $this->index->getNextIndex(2, CustomDimensions::SCOPE_ACTION));

        // should fail to get a next index
        $this->index->getNextIndex($idSite, CustomDimensions::SCOPE_ACTION);
    }

    private function createIndex($idSite, $scope, $index)
    {
        $configuration = new Configuration();
        $configuration->configureNewDimension($idSite, 'MyName', $scope, $index, false, array(), $caseSensitive = true);
    }
}
