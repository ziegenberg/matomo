<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\ArchivingMetrics\tests\Integration;

use Matomo\Common;
use Matomo\Db;
use Matomo\Period;
use Matomo\Plugins\ArchivingMetrics\Clock\Clock;
use Matomo\Plugins\ArchivingMetrics\Context;
use Matomo\Plugins\ArchivingMetrics\Timer;
use Matomo\Plugins\ArchivingMetrics\Writer\DbWriter;
use Matomo\Segment;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group ArchivingMetrics
 * @group ArchivingMetrics_TimerDb
 * @group Plugins
 */
class TimerDbTest extends IntegrationTestCase
{
    public function testItWritesAndReadsFromDatabase(): void
    {
        $period = new Period\Day(\Matomo\Date::factory('2020-11-01'));
        $segment = new Segment('', [1]);
        $context = new Context(1, $period, $segment, '');

        $timer = new Timer(true, new Clock(), new DbWriter());
        $timer->start($context);
        $timer->complete($context, [999], false);

        $rows = Db::fetchAll('SELECT * FROM ' . Common::prefixTable('archiving_metrics'));

        self::assertCount(1, $rows, 'Expected archiving_metrics table to have exactly one record.');
        self::assertSame(999, (int) $rows[0]['idarchive']);
        self::assertSame(1, (int) $rows[0]['idsite']);
        self::assertNotEmpty($rows[0]['archive_name']);
        self::assertSame('2020-11-01', $rows[0]['date1']);
        self::assertSame('2020-11-01', $rows[0]['date2']);
        self::assertIsNumeric($rows[0]['period']);
        self::assertNotEmpty($rows[0]['ts_started']);
        self::assertNotEmpty($rows[0]['ts_finished']);
        self::assertIsNumeric($rows[0]['total_time']);
        self::assertIsNumeric($rows[0]['total_time_exclusive']);
        self::assertArrayHasKey('is_temporary', $rows[0]);
        self::assertSame(0, (int) $rows[0]['is_temporary']);
    }
}
