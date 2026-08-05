<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\tests\System;

use Matomo\Matomo;
use Matomo\Plugins\Ecommerce\tests\Fixtures\AbandonedCartWithoutConversions;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

class AbandonedCartWithoutConversionsTest extends SystemTestCase
{
    /**
     * @var AbandonedCartWithoutConversions
     */
    public static $fixture;

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $idSite   = self::$fixture->idSite;
        $dateTime = self::$fixture->dateTime;

        $api = ['Goals'];
        $goalItemApi = ['Goals.getItemsSku', 'Goals.getItemsName', 'Goals.getItemsCategory'];

        return [
            [
                $api, [
                    'idSite' => $idSite,
                    'date' => $dateTime,
                    'periods' => ['day', 'week'],
                    'idGoal' => Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_CART,
                ],
            ],
            [
                $goalItemApi, [
                    'idSite'                 => $idSite,
                    'date'                   => $dateTime,
                    'periods'                => ['day', 'week'],
                    'testSuffix'             => '_AbandonedCarts',
                    'otherRequestParameters' => [
                        'abandonedCarts' => 1,
                    ],
                ],
            ],
        ];
    }

    public static function getOutputPrefix()
    {
        return 'abandonedCartWithoutConversions';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

AbandonedCartWithoutConversionsTest::$fixture = new AbandonedCartWithoutConversions();
