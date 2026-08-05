<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\FeatureFlags\Commands;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\ConsoleCommand;
use Matomo\Plugins\FeatureFlags\Commands\FeatureFlagFinder\FeatureFlagFinder;
use Matomo\Plugins\FeatureFlags\FeatureFlagStorageInterface;

class EnableFeatureFlag extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('featureflags:enable');
        $this->setDescription('Enable a given feature flag');
        $this->addRequiredArgument('featureFlagName');
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $featureFlag = FeatureFlagFinder::findFeatureFlagByName($input->getArgument('featureFlagName'));

        if ($featureFlag === null) {
            throw new \Exception("Feature flag could not be found");
        }

        /** @var FeatureFlagStorageInterface $storage */
        foreach (StaticContainer::get('featureflag.storages') as $storage) {
            $storage->enableFeatureFlag($featureFlag);
        }

        return self::SUCCESS;
    }
}
