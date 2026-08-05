<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\tests\Unit;

use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Matomo\Plugins\Diagnostics\DiagnosticService;
use Matomo\Plugins\Diagnostics\tests\Mock\DiagnosticWithError;
use Matomo\Plugins\Diagnostics\tests\Mock\DiagnosticWithSuccess;
use Matomo\Plugins\Diagnostics\tests\Mock\DiagnosticWithWarning;

class DiagnosticServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testRunDiagnostics()
    {
        $mandatoryDiagnostics = array(
            new DiagnosticWithError(),
        );
        $optionalDiagnostics = array(
            new DiagnosticWithWarning(),
            new DiagnosticWithSuccess(),
        );
        $informationDiagnostics = array(
        );

        $service = new DiagnosticService($mandatoryDiagnostics, $optionalDiagnostics, $informationDiagnostics, array());

        $report = $service->runDiagnostics();

        $results = $report->getAllResults();

        $this->assertCount(3, $results);
        $this->assertEquals('Error', $results[0]->getLabel());
        $this->assertEquals(DiagnosticResult::STATUS_ERROR, $results[0]->getStatus());
        $this->assertEquals('Warning', $results[1]->getLabel());
        $this->assertEquals(DiagnosticResult::STATUS_WARNING, $results[1]->getStatus());
        $this->assertEquals('Success', $results[2]->getLabel());
        $this->assertEquals(DiagnosticResult::STATUS_OK, $results[2]->getStatus());
    }
}
