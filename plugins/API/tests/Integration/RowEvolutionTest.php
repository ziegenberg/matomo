<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API\tests\Integration;

use Matomo\Plugins\API\RowEvolution;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group API
 * @group RowEvolutionTest
 * @group Plugins
 */
class RowEvolutionTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Fixture::createWebsite('2014-01-01 00:00:00');
    }

    public function testGetRowEvolutionShouldTriggerAnExceptionIfReportHasNoDimension()
    {
        $this->expectException(\Exception::class);
        $this->expectDeprecationMessage("Reports like VisitsSummary.get which do not have a dimension are not supported by row evolution");
        $rowEvolution = new RowEvolution();
        $rowEvolution->getRowEvolution(1, 'day', 'last7', 'VisitsSummary', 'get');
    }

    public function testGetRowEvolutionShouldNotTriggerAnExceptionIfReportHasADimension()
    {
        $rowEvolution = new RowEvolution();
        $table = $rowEvolution->getRowEvolution(1, 'day', 'last7', 'Actions', 'getPageUrls');
        $this->assertNotEmpty($table);
    }
}
