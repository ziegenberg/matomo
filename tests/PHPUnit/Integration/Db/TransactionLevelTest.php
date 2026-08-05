<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Db;

use Matomo\Db;
use Matomo\Db\Schema;
use Matomo\Db\TransactionLevel;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group TransactionLevelTest
 * @group TransactionLevel
 * @group Plugins
 */
class TransactionLevelTest extends IntegrationTestCase
{
    /**
     * @var TransactionLevel
     */
    private $level;

    /**
     * @var \Matomo\Tracker\Db|\Matomo\Db\AdapterInterface|\Matomo\Db $db
     */
    private $db;

    public function setUp(): void
    {
        parent::setUp();
        $this->db    = Db::get();
        $this->level = new TransactionLevel($this->db);
    }

    public function testCanLikelySetTransactionLevel()
    {
        $this->assertTrue($this->level->canLikelySetTransactionLevel());
    }

    public function testSetTransactionLevelForNonLockingReadsRestorePreviousStatus()
    {
        // mysql 8.0 using transaction_isolation
        $isolation = $this->db->fetchOne("SHOW GLOBAL VARIABLES LIKE 't%_isolation'");
        $isolation = "@@" . $isolation;

        $value = $this->db->fetchOne('SELECT ' . $isolation);
        $this->assertSame('REPEATABLE-READ', $value);

        $this->level->setTransactionLevelForNonLockingReads();
        $value = $this->db->fetchOne('SELECT ' . $isolation);

        $expectedIsolation = str_replace(' ', '-', Schema::getInstance()->getSupportedReadIsolationTransactionLevel());
        $this->assertSame($expectedIsolation, $value);
        $this->level->restorePreviousStatus();

        $value = $this->db->fetchOne('SELECT ' . $isolation);
        $this->assertSame('REPEATABLE-READ', $value);
    }
}
