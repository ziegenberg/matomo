<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\FeatureFlags\tests\System\Commands;

use Matomo\Config;
use Matomo\Container\StaticContainer;
use Matomo\DI;
use Matomo\Tests\Framework\TestCase\ConsoleCommandTestCase;

class DisableFeatureFlagCommandTest extends ConsoleCommandTestCase
{
    public function testDisableFeatureFlagAddsToConfig()
    {
        $container = StaticContainer::getContainer();
        $container->set('featureflag.dir_of_feature_flags', DI::string('tests/System/Commands/FeatureFlags'));
        $container->get(Config::class)->FeatureFlags =  ['SystemTest_feature' => 'enabled'];

        $this->applicationTester->run([
            'command' => 'featureflags:disable',
            'featureFlagName' => 'SystemTest',
        ]);

        $flags = $container->get(Config::class)->FeatureFlags;

        $this->assertEquals(['SystemTest_feature' => 'disabled'], $flags);
    }
}
