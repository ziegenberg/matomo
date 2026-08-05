<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Tracker\Db\Pdo;

use Matomo\Tests\Framework\TestCase\IntegrationTestCase;
use Matomo\Tracker\Db\Pdo\Mysql;
use Matomo\Config;
use Exception;

class MySqlTest extends IntegrationTestCase
{
    /**
     * @var Mysql
     */
    private $mysql;

    public function setUp(): void
    {
        parent::setUp();

        $configDb = Config::getInstance()->database;
        $this->mysql = new Mysql($configDb);
    }


    public function testIsMysqlServerHasGoneAwayError()
    {
        $this->assertFalse($this->mysql->isMysqlServerHasGoneAwayError(new Exception('fff')));
        $this->assertFalse($this->mysql->isMysqlServerHasGoneAwayError(new Exception('[1234] Duplicate Entry')));
        $this->assertTrue($this->mysql->isMysqlServerHasGoneAwayError(new Exception('[2006] foo')));
        $this->assertTrue($this->mysql->isMysqlServerHasGoneAwayError(new Exception('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away')));
    }

    public function testReconnectWontFail()
    {
        $e = new Exception('fff');
        $this->assertNull($this->mysql->reconnect($e));
    }
}
