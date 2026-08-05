<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserLanguage\tests\System;

use Matomo\Plugins\UserLanguage\tests\Fixtures\LanguageFixture;
use Matomo\Tests\Framework\TestCase\SystemTestCase;

/**
 * @group GetLanguageSystemTest
 * @group Plugins
 * @group UserLanguage
 */
class GetLanguageSystemTest extends SystemTestCase
{
    public static $fixture = null;

    public static function getOutputPrefix()
    {
        return '';
    }

    /**
     * @param $api
     * @param $params
     * @dataProvider    getApiForTesting
     * @group           GetLanguageSystemTest
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    /**
     * @return array
     */
    public function getApiForTesting()
    {
        $apiToCall = [
            "UserLanguage.getLanguage",
            "UserLanguage.getLanguageCode",
        ];

        $apiToTest = [];

        $apiToTest[] = [
                            $apiToCall,
                            [
                                'idSite'  => self::$fixture->idSite,
                                'date'    => self::$fixture->dateTime,
                                'periods' => ['day'],
                            ],
                       ];

        return $apiToTest;
    }


    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

GetLanguageSystemTest::$fixture = new LanguageFixture();
