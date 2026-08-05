<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\tests\Unit\Diagnostic;

use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResultItem;

class DiagnosticResultTest extends \PHPUnit\Framework\TestCase
{
    public function testGetStatusShouldReturnTheWorstStatus()
    {
        $result = new DiagnosticResult('Label');

        $this->assertEquals(DiagnosticResult::STATUS_OK, $result->getStatus());

        $result->addItem(new DiagnosticResultItem(DiagnosticResult::STATUS_WARNING));
        $this->assertEquals(DiagnosticResult::STATUS_WARNING, $result->getStatus());

        $result->addItem(new DiagnosticResultItem(DiagnosticResult::STATUS_ERROR));
        $this->assertEquals(DiagnosticResult::STATUS_ERROR, $result->getStatus());
    }

    public function testSingleResultShouldReturnAResultWithASingleItem()
    {
        $result = DiagnosticResult::singleResult('Label', DiagnosticResult::STATUS_ERROR);

        $this->assertInstanceOf('Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult', $result);
        $this->assertEquals('Label', $result->getLabel());
        $this->assertEquals(DiagnosticResult::STATUS_ERROR, $result->getStatus());
    }
}
