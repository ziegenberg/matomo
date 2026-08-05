<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\Diagnostic;

use Matomo\Common;
use Matomo\Translation\Translator;

/**
 * Information about the current user.
 */
class UserInformational implements Diagnostic
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function execute()
    {
        $results = [];

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $results[] = DiagnosticResult::informationalResult('User Agent', $_SERVER['HTTP_USER_AGENT']);
        }

        $results[] = DiagnosticResult::informationalResult('Browser Language', Common::getBrowserLanguage());

        return $results;
    }
}
