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

class DeleteFeatureFlagCommandTest extends ConsoleCommandTestCase
{
    public function testDeleteFeatureFlagRemovesFromConfig()
    {
        $container = StaticContainer::getContainer();
        $container->set('featureflag.dir_of_feature_flags', DI::string('tests/System/Commands/FeatureFlags'));
        $container->get(Config::class)->FeatureFlags = ['ExampleFeatureThatDoesntExistAsClass_feature' => 'enabled'];

        $this->applicationTester->run([
            'command' => 'featureflags:delete',
            'featureFlagName' => 'ExampleFeatureThatDoesntExistAsClass',
        ]);

        $flags = $container->get(Config::class)->FeatureFlags;
        $this->assertArrayNotHasKey('ExampleFeatureThatDoesntExistAsClass_feature', $flags);
    }
}
