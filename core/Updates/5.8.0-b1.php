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
use Matomo\Db;
use Matomo\DbHelper;
use Matomo\Plugins\BotTracking\BotDetector;
use Matomo\Plugins\BotTracking\Dao\BotRequestsDao;
use Matomo\Segment;
use Matomo\Updater;
use Matomo\Updater\Migration\Custom as CustomMigration;
use Matomo\Updates;
use Matomo\Updater\Migration\Factory as MigrationFactory;

class Updates_5_8_0_b1 extends Updates
{
    private MigrationFactory $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater): array
    {
        $migrations = [];

        $tableName = BotRequestsDao::getPrefixedTableName();
        if (DbHelper::tableExists($tableName)) {
            $migrations[] = $this->migration->db->boundSql(sprintf('UPDATE `%s` SET bot_type = ? WHERE bot_type = ?', $tableName), [BotDetector::BOT_TYPE_AI_CHATBOT, 'ai_assistant']);
            $earliestDatesBySite = Db::fetchAll(
                sprintf('SELECT idsite, DATE(MIN(server_time)) as earliest_date FROM `%s` GROUP BY idsite', $tableName)
            );

            foreach ($earliestDatesBySite as $siteData) {
                if (empty($siteData['idsite']) || empty($siteData['earliest_date'])) {
                    continue;
                }

                $siteId = (int)$siteData['idsite'];
                $startDate = Date::factory($siteData['earliest_date'])->subDay(1)->toString('Y-m-d');
                $migrationHint = sprintf(
                    './console core:invalidate-report-data --plugin=BotTracking --sites=%d --dates=%s,today --segment=',
                    $siteId,
                    $startDate
                );

                $migrations[] = new CustomMigration(function () use ($siteId, $startDate) {
                    $invalidator = StaticContainer::get(ArchiveInvalidator::class);
                    $invalidator->scheduleReArchiving([$siteId], 'BotTracking', null, Date::factory($startDate), new Segment('', [$siteId]));
                }, $migrationHint);
            }
        }

        return $migrations;
    }

    public function doUpdate(Updater $updater): void
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
