<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Updater;
use Matomo\Updates;
use Matomo\Updater\Migration\Factory as MigrationFactory;

class Updates_1_5_b4 extends Updates
{
    private MigrationFactory $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater)
    {
        return array(
            $this->migration->db->addColumn('site', 'ecommerce', 'TINYINT DEFAULT 0'),
        );
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
