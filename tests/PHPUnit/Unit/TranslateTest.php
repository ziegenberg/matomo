<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Unit;

use Matomo\Container\StaticContainer;

/**
 * @group Translation
 */
class TranslateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Dataprovider for testClean
     */
    public function getCleanTestData()
    {
        return array(
            // empty string
            array("", ''),
            // newline
            array("\n", ''),
            // leading and trailing whitespace
            array(" a \n", 'a'),
            // single / double quotes
            array(" &quot;it&#039;s&quot; ", '"it\'s"'),
            // html special characters
            array("&lt;tag&gt;", '<tag>'),
            // other html entities
            array("&hellip;", '…'),
        );
    }

    /**
     * @group Core
     * @dataProvider getCleanTestData
     */
    public function testClean($data, $expected)
    {
        $this->assertEquals($expected, StaticContainer::get('Matomo\Translation\Translator')->clean($data));
    }
}
