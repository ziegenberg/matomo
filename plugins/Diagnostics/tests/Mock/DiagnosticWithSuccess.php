<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\tests\Mock;

use Matomo\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;

class DiagnosticWithSuccess implements Diagnostic
{
    public function execute()
    {
        return array(
            DiagnosticResult::singleResult('Success', DiagnosticResult::STATUS_OK, 'Comment'),
        );
    }
}
