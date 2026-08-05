<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\Diagnostic;

use Matomo\Db;
use Matomo\Matomo;
use Matomo\SettingsPiwik;
use Matomo\Translation\Translator;

/**
 * Check if Piwik can use LOAD DATA INFILE.
 */
class DbReaderCheck implements Diagnostic
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function execute()
    {
        if (!SettingsPiwik::isMatomoInstalled()) {
            // Skip the diagnostic if Matomo is being installed
            return [];
        }

        if (!Db::hasReaderConfigured()) {
            // only show an entry when reader is actually configured
            return [];
        }

        $label = $this->translator->translate('Diagnostics_DatabaseReaderConnection');

        try {
            Db::getReader();
            return array(DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK, ''));
        } catch (\Exception $e) {
        }

        $comment = Matomo::translate('Installation_CannotConnectToDb');
        return array(DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_WARNING, $comment));
    }
}
