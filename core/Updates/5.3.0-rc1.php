<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Plugins\FeatureFlags\FeatureFlagManager;
use Matomo\Updater;
use Matomo\Updater\Migration\Custom as CustomMigration;
use Matomo\Updater\Migration\Factory as MigrationFactory;
use Matomo\Updates;

class Updates_5_3_0_rc1 extends Updates
{
    private MigrationFactory $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        $commandString = './console featureflags:delete ImprovedAllWebsitesDashboard';

        $deleteFeatureFlag = new CustomMigration(
            [FeatureFlagManager::class, 'deleteFeatureFlag'],
            $commandString,
            ['ImprovedAllWebsitesDashboard']
        );

        return [$deleteFeatureFlag];
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
