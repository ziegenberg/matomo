<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PrivacyManager\tests\Integration;

use Matomo\DataTable;
use Matomo\Date;
use Matomo\Plugins\PrivacyManager\PrivacyManager;
use Matomo\Plugins\PrivacyManager\API;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Class Plugins_SitesManagerTest
 *
 * @group Plugins
 */
class PrivacyManagerTest extends IntegrationTestCase
{
    public const DELETE_LOGS_OLDER_THAN = 1000;
    public const DELETE_LOGS_OLDER_THAN_CNIL = 759;

    /**
     * @var PrivacyManager
     */
    private $manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = new PrivacyManager();
        \Matomo\Option::set('delete_logs_enable', 1);
        \Matomo\Option::set('delete_logs_older_than', self::DELETE_LOGS_OLDER_THAN);
        \Matomo\Option::set('delete_reports_keep_week_reports', 1);
    }

    public function tearDown(): void
    {
        unset($_GET['date']);
        unset($_GET['period']);
        parent::tearDown();
    }

    public function testGetPurgeDataSettingsShouldUseOnlyConfigValuesIfUIisDisabled()
    {
        $this->setUIEnabled(false);

        $settings = $this->manager->getPurgeDataSettings();
        $expected = $this->getDefaultPurgeSettings();

        $this->assertEquals($expected, $settings);
    }

    public function testGetPurgeDataSettingsShouldAlsoUseOptionValuesIfUIisEnabled()
    {
        $this->setUIEnabled(true);

        $settings = $this->manager->getPurgeDataSettings();
        $expected = $this->getDefaultPurgeSettings();

        $expected['delete_logs_enable'] = 1;
        $expected['delete_logs_older_than'] = self::DELETE_LOGS_OLDER_THAN;
        $expected['delete_reports_keep_week_reports'] = 1;

        $this->assertEquals($expected, $settings);
    }

    public function testGetPurgeDataSettingsCnilPolicyDisabled()
    {
        $this->setUIEnabled(true);

        API::getInstance()->setComplianceStatus('all', 'cnil_v1', $enabled = false);

        $settings = $this->manager->getPurgeDataSettings();
        $expected = $this->getDefaultPurgeSettings();
        $expected['delete_logs_enable'] = 1;
        $expected['delete_logs_older_than'] = self::DELETE_LOGS_OLDER_THAN;
        $expected['delete_reports_keep_week_reports'] = 1;

        $this->assertEquals($expected, $settings);
    }

    public function testGetPurgeDataSettingsCnilPolicyEnabled()
    {
        $this->setUIEnabled(true);

        API::getInstance()->setComplianceStatus('all', 'cnil_v1', $enabled = true);

        $settings = $this->manager->getPurgeDataSettings();
        $expected = $this->getDefaultPurgeSettings();
        $expected['delete_logs_enable'] = 1;
        $expected['delete_logs_older_than'] = self::DELETE_LOGS_OLDER_THAN_CNIL;
        $expected['delete_reports_keep_week_reports'] = 1;

        $this->assertEquals($expected, $settings);
    }

    public function testHaveLogsBeenPurgedWhenDateIsRecent()
    {
        $this->setUIEnabled(true);
        $_GET['date'] = Date::now()->subDay(self::DELETE_LOGS_OLDER_THAN - 2)->toString();
        $_GET['period'] = 'date';

        $this->assertFalse(PrivacyManager::haveLogsBeenPurged($dataTable = null));
    }

    public function testHaveLogsBeenPurgedWhenDateIsPastLogDeleteAndNoDataTableIsGiven()
    {
        $this->setUIEnabled(true);
        $_GET['date'] = Date::now()->subDay(self::DELETE_LOGS_OLDER_THAN + 2)->toString();
        $_GET['period'] = 'date';

        $this->assertTrue(PrivacyManager::haveLogsBeenPurged($dataTable = null));
    }

    public function testHaveLogsBeenPurgedWhenDateIsPastLogDeleteButLogsAreDisabled()
    {
        $this->setUIEnabled(true);
        \Matomo\Option::set('delete_logs_enable', 0);

        $_GET['date'] = Date::now()->subDay(self::DELETE_LOGS_OLDER_THAN + 1000)->toString();
        $_GET['period'] = 'date';

        $this->assertFalse(PrivacyManager::haveLogsBeenPurged($dataTable = null));
    }

    public function testHaveLogsBeenPurgedWhenDateIsPastLogDeleteShouldNotBeDeletedIfDataTableHasData()
    {
        $this->setUIEnabled(true);
        $_GET['date'] = Date::now()->subDay(self::DELETE_LOGS_OLDER_THAN + 2)->toString();
        $_GET['period'] = 'date';

        $dataTable = DataTable::makeFromSimpleArray(array(
            array('label' => 'myLabel'),
            array('label' => 'my2ndLabel'),
        ));
        $this->assertFalse(PrivacyManager::haveLogsBeenPurged($dataTable));
    }

    public function testHaveLogsBeenPurgedWhenDateIsPastLogDeleteShouldBeDeletedIfDataTableHasNoData()
    {
        $this->setUIEnabled(true);
        $_GET['date'] = Date::now()->subDay(self::DELETE_LOGS_OLDER_THAN + 2)->toString();
        $_GET['period'] = 'date';

        $dataTable = new DataTable();
        $this->assertTrue(PrivacyManager::haveLogsBeenPurged($dataTable));
    }

    public function testHaveLogsBeenPurgedWhenPassingManualLogDeletionDateValueShouldAssumeLogDeletionIsEnabled()
    {
        $this->setUIEnabled(true);
        \Matomo\Option::set('delete_logs_enable', 0);

        $_GET['date'] = Date::now()->subDay(498)->toString();
        $_GET['period'] = 'date';

        $this->assertFalse(PrivacyManager::haveLogsBeenPurged($dataTable = null, $days = 500));

        $_GET['date'] = Date::now()->subDay(502)->toString();
        $_GET['period'] = 'date';

        $this->assertTrue(PrivacyManager::haveLogsBeenPurged($dataTable = null, $days = 500));
    }

    public function testSavePurgeDataSettings()
    {
        PrivacyManager::savePurgeDataSettings(
            [
                'delete_logs_enable'                   => '',
                'delete_logs_schedule_lowest_interval' => '7',
                'delete_logs_older_than'               => 180,
                'delete_logs_max_rows_per_query'       => null, // should not be stored
                'delete_reports_enable'                => '1',
                'delete_reports_older_than'            => 7,
                'delete_reports_keep_basic_metrics'    => '1',
                'delete_reports_keep_day_reports'      => '',
                'delete_reports_keep_week_reports'     => 1.0,
                'delete_reports_keep_month_reports'    => false,
                'delete_reports_keep_year_reports'     => true,
                'delete_reports_keep_range_reports'    => '1 ',
                'delete_reports_keep_segment_reports'  => '0',
            ]
        );

        self::assertSame(
            [
                'delete_logs_enable'                   => 0,
                'delete_logs_schedule_lowest_interval' => 7,
                'delete_logs_older_than'               => 180,
                'delete_logs_max_rows_per_query'       => 100000,
                'delete_logs_unused_actions_schedule_lowest_interval' => 30,
                'delete_logs_unused_actions_max_rows_per_query'       => 100000,
                'enable_auto_database_size_estimate'   => 1,
                'enable_database_size_estimate'        => 1,
                'delete_reports_enable'                => 1,
                'delete_reports_older_than'            => 7,
                'delete_reports_keep_basic_metrics'    => 1,
                'delete_reports_keep_day_reports'      => 0,
                'delete_reports_keep_week_reports'     => 1,
                'delete_reports_keep_month_reports'    => 0,
                'delete_reports_keep_year_reports'     => 1,
                'delete_reports_keep_range_reports'    => 1,
                'delete_reports_keep_segment_reports'  => 0,
            ],
            PrivacyManager::getPurgeDataSettings()
        );
    }

    private function setUIEnabled($enabled)
    {
        \Matomo\Config::getInstance()->General['enable_delete_old_data_settings_admin'] = $enabled;
    }

    private function getDefaultPurgeSettings()
    {
        $expected = array(
            'delete_logs_enable' => 0,
            'delete_logs_schedule_lowest_interval' => 7,
            'delete_logs_older_than' => 180,
            'delete_logs_max_rows_per_query' => 100000,
            'delete_logs_unused_actions_max_rows_per_query' => 100000,
            'delete_logs_unused_actions_schedule_lowest_interval' => 30,
            'enable_auto_database_size_estimate' => 1,
            'enable_database_size_estimate' => 1,
            'delete_reports_enable' => 0,
            'delete_reports_older_than' => 12,
            'delete_reports_keep_basic_metrics' => 1,
            'delete_reports_keep_day_reports' => 0,
            'delete_reports_keep_week_reports' => 0,
            'delete_reports_keep_month_reports' => 1,
            'delete_reports_keep_year_reports' => 1,
            'delete_reports_keep_range_reports' => 0,
            'delete_reports_keep_segment_reports' => 0,
        );
        return $expected;
    }
}
