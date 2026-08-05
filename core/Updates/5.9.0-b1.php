<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Plugins\CoreAdminHome\Commands\MigrateUserScopedSettings;
use Matomo\Updater;
use Matomo\Updater\Migration\Custom as CustomMigration;
use Matomo\Updates;

class Updates_5_9_0_b1 extends Updates
{
    public function getMigrations(Updater $updater)
    {
        return [
            new CustomMigration(
                [MigrateUserScopedSettings::class, 'migrate'],
                './console core:matomo590-migrate-user-scoped-settings'
            ),
        ];
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
