<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration\Session\SaveHandler;

use Matomo\Session;
use Matomo\Session\SaveHandler\DbTable;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class DbTableTest extends IntegrationTestCase
{
    /**
     * @var DbTable
     */
    private $testInstance;

    public function setUp(): void
    {
        parent::setUp();
        $this->testInstance = new DbTable(Session::getDbTableConfig());
    }

    public function testReadReturnsTheSessionDataCorrectly()
    {
        $this->testInstance->write('testid', 'testdata');

        $result = $this->testInstance->read('testid');

        $this->assertEquals('testdata', $result);
    }
}
