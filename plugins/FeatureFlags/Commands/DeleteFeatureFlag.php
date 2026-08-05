<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\FeatureFlags\Commands;

use Matomo\Plugin\ConsoleCommand;
use Matomo\Plugins\FeatureFlags\FeatureFlagManager;

class DeleteFeatureFlag extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('featureflags:delete');
        $this->setDescription('Delete a given feature flag');
        $this->addRequiredArgument('featureFlagName');
    }

    protected function doExecute(): int
    {
        $featureFlagName = $this->getInput()->getArgument('featureFlagName');

        if ($featureFlagName === null) {
            throw new \Exception("Feature flag could not be found");
        }

        FeatureFlagManager::deleteFeatureFlag($featureFlagName);

        return self::SUCCESS;
    }
}
