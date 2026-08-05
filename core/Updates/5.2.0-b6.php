<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Archive\ArchiveInvalidator;
use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Updater;
use Matomo\Updater\Migration\Custom as CustomMigration;
use Matomo\Updater\Migration\Factory as MigrationFactory;
use Matomo\Updates;

class Updates_5_2_0_b6 extends Updates
{
    private MigrationFactory $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        $startOfCurrentMonth = Date::now()->toString('Y-m') . '-01';

        $commandToExecute = sprintf(
            './console core:invalidate-report-data --dates=%s,today --plugin=Actions.Actions_hits',
            $startOfCurrentMonth
        );

        $migrations = [
            new CustomMigration(function () use ($startOfCurrentMonth) {
                $invalidator = StaticContainer::get(ArchiveInvalidator::class);
                $invalidator->scheduleReArchiving('all', 'Actions', 'Actions_hits', Date::factory($startOfCurrentMonth));
            }, $commandToExecute),
        ];

        return $migrations;
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
