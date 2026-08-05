<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreUpdater;

use Matomo\Config;
use Matomo\Container\StaticContainer;
use Matomo\Db;
use Matomo\DbHelper;

class Tasks extends \Matomo\Plugin\Tasks
{
    public function schedule()
    {
        $this->daily('sendNotificationIfUpdateAvailable', null, self::LOWEST_PRIORITY);

        $dbSettings   = new \Matomo\Db\Settings();
        $settings = StaticContainer::get('Matomo\Plugins\CoreUpdater\SystemSettings');

        if ($dbSettings->getUsedCharset() !== 'utf8mb4' && DbHelper::getDefaultCharset() === 'utf8mb4' && !empty($settings->updateToUtf8mb4) && $settings->updateToUtf8mb4->getValue()) {
            $this->daily('convertToUtf8mb4', null, self::HIGHEST_PRIORITY);
        }
    }

    public function sendNotificationIfUpdateAvailable()
    {
        $coreUpdateCommunication = new UpdateCommunication();
        if ($coreUpdateCommunication->isEnabled()) {
            $coreUpdateCommunication->sendNotificationIfUpdateAvailable();
        }
    }

    public function convertToUtf8mb4()
    {
        $queries = DbHelper::getUtf8mb4ConversionQueries();

        foreach ($queries as $query) {
            Db::get()->exec($query);
        }

        $config                      = Config::getInstance();
        $config->database['charset'] = 'utf8mb4';
        $config->forceSave();

        $settings = StaticContainer::get('Matomo\Plugins\CoreUpdater\SystemSettings');
        $settings->updateToUtf8mb4->setValue(false);
    }
}
