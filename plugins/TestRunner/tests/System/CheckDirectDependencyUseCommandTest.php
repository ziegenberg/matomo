<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\TestRunner\tests\System;

use Matomo\Plugins\TestRunner\Commands\CheckDirectDependencyUse;
use Matomo\Tests\Framework\TestCase\SystemTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @group TestRunner
 * @group TestRunner
 * @group Plugins
 */
class CheckDirectDependencyUseCommandTest extends SystemTestCase
{
    /**
     * @dataProvider getTestDataForDependencyCheck()
     */
    public function testCommand($pluginName, $expectedResult)
    {
        $console = new \Matomo\Console(self::$fixture->piwikEnvironment);
        $checkDirectDependencyUse = new CheckDirectDependencyUse();
        $console->addCommands([$checkDirectDependencyUse]);
        $command = $console->find('tests:check-direct-dependency-use');
        $arguments = array(
            'command' => 'tests:check-direct-dependency-use',
            '--plugin' => $pluginName,
        );
        $inputObject = new ArrayInput($arguments);
        $command->run($inputObject, new NullOutput());

        $this->assertEquals($expectedResult, $checkDirectDependencyUse->usesFoundList[$pluginName]);
    }

    public function getTestDataForDependencyCheck()
    {
        return [
            [
                'TestRunner',
                [
                    'Symfony\Component\Console' => [
                        'TestRunner/tests/System/CheckDirectDependencyUseCommandTest.php',
                    ],
                ],
            ],
            [
                'Provider',
                [
                    'Matomo\Network' => [
                        'Provider/Columns/Provider.php',
                    ],
                    'Symfony\Component\Console' => [
                        'Provider/tests/System/CheckDirectDependencyUseCommandTest.php',
                    ],
                ],
            ],
        ];
    }
}
