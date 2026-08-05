<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\tests\Unit;

use PHPUnit\Framework\TestCase;
use Matomo\DataTable;
use Matomo\DataTable\Row;
use Matomo\Date;
use Matomo\Plugins\CoreHome\Columns\Metrics\EvolutionMetric;

/**
 * @group CoreHome
 * @group CoreHomeTest
 * @group EvolutionMetric
 */
class EvolutionMetricTest extends TestCase
{
    public function testShouldDoProportionalComparisionIfCurrentPeriodIncomplete()
    {
        $currentData = new DataTable();
        $cPeriod = new \Matomo\Period\Week(Date::factory('2021-10-10'));
        $currentData->setMetadata('period', $cPeriod);

        // If the archived date meta data value exists on the row then it will be used
        // as the current date for calculation purposes, we can use this to consistently test the
        // ratio calculation by supplying a fixed set of dates that should result in a 0.5 ratio

        $row = new Row();
        $row->setMetadata(DataTable::ARCHIVED_DATE_METADATA_NAME, '2021-10-07 00:00:00');

        $pastData = new DataTable();
        $sPeriod = new \Matomo\Period\Week(Date::factory('2021-10-03'));
        $pastData->setMetadata('period', $sPeriod);

        $ratio = EvolutionMetric::getRatio($currentData, $pastData, $row);

        $this->assertEquals(0.429, $ratio);
    }

    public function testShouldNotDoProportionalComparisionIfCurrentPeriodComplete()
    {
        $currentData = new DataTable();
        $cPeriod = new \Matomo\Period\Week(Date::factory('2021-10-10'));
        $currentData->setMetadata('period', $cPeriod);

        $pastData = new DataTable();
        $sPeriod = new \Matomo\Period\Week(Date::factory('2021-10-03'));
        $pastData->setMetadata('period', $sPeriod);

        $ratio = EvolutionMetric::getRatio($currentData, $pastData, new Row());

        $this->assertEquals(1, $ratio);
    }
}
