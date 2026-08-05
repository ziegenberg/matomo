<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites\tests\Integration;

use Matomo\Access;
use Matomo\FrontController;
use Matomo\Plugins\MultiSites\API as APIMultiSites;
use Matomo\Plugins\SitesManager\API as APISitesManager;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Class Plugins_MultiSitesTest
 *
 * @group Plugins
 */
class MultiSitesTest extends IntegrationTestCase
{
    protected $idSiteAccess;

    public function setUp(): void
    {
        parent::setUp();

        $access = Access::getInstance();
        $access->setSuperUserAccess(true);

        $this->idSiteAccess = APISitesManager::getInstance()->addSite("test", "http://test");

        \Matomo\Plugin\Manager::getInstance()->loadPlugins(['MultiSites', 'VisitsSummary', 'Actions']);
        \Matomo\Plugin\Manager::getInstance()->installLoadedPlugins();
    }

    /**
     * Testing that getOne returns a row even when there are no data
     * This is necessary otherwise ResponseBuilder throws 'Call to a member function getColumns() on a non-object'
     *
     * @group Plugins
     */
    public function testWhenNoDataGetOneReturnsRow()
    {
        $dataTable = APIMultiSites::getInstance()->getOne($this->idSiteAccess, 'month', '01-01-2010');
        $this->assertEquals(1, $dataTable->getRowsCount());

        // safety net
        $this->assertEquals(0, $dataTable->getFirstRow()->getColumn('nb_visits'));
    }

    /**
     * Testing that getOne does not error out when format=rss, #10407
     *
     * @group Plugins
     */
    public function testWhenRssFormatGetOneDoesNotError()
    {
        $_GET = [
            'method' => 'MultiSites.getOne',
            'idSite' => $this->idSiteAccess,
            'period' => 'month',
            'date'   => 'last10',
            'format'   => 'rss',
        ];

        $output = FrontController::getInstance()->fetchDispatch('API');

        self::assertStringContainsString('<item>', $output);
        self::assertStringContainsString('</rss>', $output);
        self::assertStringNotContainsString('error', $output);

        $_GET = [];
    }
}
