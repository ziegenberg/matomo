<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Tests\Integration;

use Matomo\ArchiveProcessor\Rules;
use Matomo\CronArchive;
use Matomo\FrontController;
use Matomo\Option;
use Matomo\Tests\Framework\Fixture;
use Matomo\Tests\Framework\TestCase\IntegrationTestCase;

class ReportRenderingTest extends IntegrationTestCase
{
    public function testReportHasCorrectNotificationWhenReportHasNoDataAndArchivingHasNotRunRecently()
    {
        $idSite = Fixture::createWebsite('2012-01-02 03:04:44');
        Option::set(CronArchive::OPTION_ARCHIVING_FINISHED_TS, time() - 120000);
        Option::set(Rules::OPTION_BROWSER_TRIGGER_ARCHIVING, 0);

        $_GET['idSite'] = $idSite;
        $_GET['date'] = '2012-05-06';
        $_GET['period'] = 'day';

        $frontController = FrontController::getInstance();
        $response = $frontController->dispatch('DevicesDetection', 'getBrand');

        self::assertStringContainsString('Diagnostics_NoDataForReportArchivingNotRun', $response);
    }
}
