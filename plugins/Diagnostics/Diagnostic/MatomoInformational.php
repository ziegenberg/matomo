<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\Diagnostic;

use Matomo\DbHelper;
use Matomo\Option;
use Matomo\SettingsPiwik;
use Matomo\Translation\Translator;
use Matomo\Updater;
use Matomo\Version;

/**
 * Information about Matomo itself
 */
class MatomoInformational implements Diagnostic
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function execute()
    {
        $results = [];

        $results[] = DiagnosticResult::informationalResult('Matomo Version', Version::VERSION);

        if (SettingsPiwik::isMatomoInstalled()) {
            $results[] = DiagnosticResult::informationalResult('Matomo Update History', Option::get(Updater::OPTION_KEY_MATOMO_UPDATE_HISTORY));
            $results[] = DiagnosticResult::informationalResult('Matomo Install Version', $this->getInstallVersion());
            $results[] = DiagnosticResult::informationalResult('Latest Available Version', Option::get(\Matomo\Plugins\CoreUpdater\Updater::OPTION_LATEST_VERSION));
            $results[] = DiagnosticResult::informationalResult('Is Git Deployment', SettingsPiwik::isGitDeployment());
        }

        return $results;
    }

    private function getInstallVersion()
    {
        try {
            $version = DbHelper::getInstallVersion();
            if (empty($version)) {
                $version = 'Unknown - pre 3.8.';
            }
            return $version;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
