<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MobileMessaging\Diagnostic;

use Matomo\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Matomo\Plugins\MobileMessaging\API;
use Matomo\SettingsPiwik;
use Matomo\Translation\Translator;

/**
 * Information about Matomo itself
 */
class MobileMessagingInformational implements Diagnostic
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function execute()
    {
        if (SettingsPiwik::isMatomoInstalled()) {
            $provider = API::getInstance()->getSMSProvider();

            $label = 'Mobile Messaging SMS Provider';

            if (empty($provider)) {
                return [DiagnosticResult::informationalResult($label, 'not configured')];
            }

            try {
                $creditsLeft = API::getInstance()->getCreditLeft();
                return [DiagnosticResult::informationalResult(
                    $label,
                    sprintf('%s (%s credits left)', $provider, $creditsLeft)
                )];
            } catch (\Exception $e) {
                return [DiagnosticResult::singleResult(
                    $label,
                    DiagnosticResult::STATUS_ERROR,
                    sprintf('%s<br /><b>Communication error:</b> %s', $provider, $e->getMessage())
                )];
            }
        }
        return [];
    }
}
