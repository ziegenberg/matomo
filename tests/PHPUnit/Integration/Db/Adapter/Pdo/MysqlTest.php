<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Db\Adapter\Pdo;

use Matomo\Db\Adapter\Pdo\Mysql;
use Exception;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class MysqlTest extends IntegrationTestCase
{
    public function testIsPdoErrorNumber()
    {
        $e = new Exception('Error query: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry');
        $this->assertTrue(Mysql::isPdoErrorNumber($e, 1062));
        $this->assertTrue(Mysql::isPdoErrorNumber($e, '1062'));

        $this->assertFalse(Mysql::isPdoErrorNumber($e, '2300'));
        $this->assertFalse(Mysql::isPdoErrorNumber($e, '23000'));
    }
}
