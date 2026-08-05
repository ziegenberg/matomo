<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Diagnostic;

use Matomo\Matomo;
use Matomo\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Matomo\Plugins\UserCountry\LocationProvider;
use Matomo\SettingsPiwik;
use Matomo\Translation\Translator;

/**
 * Check the geolocation setup.
 */
class GeolocationDiagnostic implements Diagnostic
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

        $label = $this->translator->translate('UserCountry_Geolocation');

        $currentProviderId = LocationProvider::getCurrentProviderId();
        $allProviders = LocationProvider::getAllProviderInfo();

        $providerStatus = $allProviders[$currentProviderId]['status'] ?? LocationProvider::NOT_INSTALLED;

        $providerWarning = $allProviders[$currentProviderId]['usageWarning'] ?? null;
        $statusMessage = $allProviders[$currentProviderId]['statusMessage'] ?? null;

        if ($providerStatus === LocationProvider::BROKEN) {
            $message = Matomo::translate('UserCountry_GeolocationProviderBroken', '<strong>' . $allProviders[$currentProviderId]['title'] . '</strong>');
            if ($statusMessage) {
                $message .= '<br /><br />' . $statusMessage;
            }
            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $message)];
        }

        if ($providerStatus === LocationProvider::NOT_INSTALLED) {
            $provider = $allProviders[$currentProviderId] ?? null;

            if ($provider) {
                $message = Matomo::translate('UserCountry_GeolocationProviderBroken', '<strong>' . $allProviders[$currentProviderId]['title'] . '</strong>');
            } else {
                $message = Matomo::translate('UserCountry_GeolocationProviderUnavailable', '<strong>' . LocationProvider::getCurrentProviderId() . '</strong>');
            }

            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $message)];
        }

        if (!empty($providerWarning)) {
            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_WARNING, $providerWarning)];
        }

        $availableInfo = LocationProvider::getProviderById($currentProviderId)->getSupportedLocationInfo();
        $message = sprintf("%s (%s)", $currentProviderId, implode(', ', array_keys(array_filter($availableInfo))));

        return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK, $message)];
    }
}
