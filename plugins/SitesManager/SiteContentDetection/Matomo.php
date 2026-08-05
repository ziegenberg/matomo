<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\SitesManager\SiteContentDetection;

use Matomo\API\Request;
use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin\Manager;
use Matomo\Plugins\CustomVariables\CustomVariables;
use Matomo\Plugins\PrivacyManager\DoNotTrackHeaderChecker;
use Matomo\Site;
use Matomo\SiteContentDetector;
use Matomo\Url;
use Matomo\View;

class Matomo extends SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return Matomo::translate('CoreAdminHome_JavaScriptCode');
    }

    public static function getIcon(): string
    {
        return './plugins/SitesManager/images/code.svg';
    }

    public static function getContentType(): int
    {
        return self::TYPE_TRACKER;
    }

    public static function getPriority(): int
    {
        return 1;
    }

    public function isDetected(?string $data = null, ?array $headers = null): bool
    {
        $tests = ['/matomo\.js/i', '/piwik\.js/i', '/_paq\.push/i'];
        foreach ($tests as $test) {
            if (preg_match($test, $data) === 1) {
                return true;
            }
        }

        return false;
    }

    public function renderInstructionsTab(SiteContentDetector $detector): string
    {
        $view = new View('@SitesManager/_matomoTabInstructions');
        $dntChecker = new DoNotTrackHeaderChecker();
        $maxCustomVariables = 0;
        $matomoUrl = Url::getCurrentUrlWithoutFileName();
        $idSite = \Matomo\Request::fromRequest()->getIntegerParameter('idSite');
        $jsTag = Request::processRequest('SitesManager.getJavascriptTag', ['idSite' => $idSite, 'piwikUrl' => $matomoUrl]);

        if (Manager::getInstance()->isPluginActivated('CustomVariables')) {
            $maxCustomVariables = CustomVariables::getNumUsableCustomVariables();
        }

        $view->jsTag = $jsTag;
        $view->isJsTrackerInstallCheckAvailable = Manager::getInstance()->isPluginActivated('JsTrackerInstallCheck');
        $view->serverSideDoNotTrackEnabled = $dntChecker->isActive();
        $view->maxCustomVariables = $maxCustomVariables;
        $view->defaultSiteDecoded = [
            'id' => $idSite,
            'name' => Common::unsanitizeInputValue(Site::getNameFor($idSite)),
        ];
        $view->notificationMessage = $this->getConsentManagerNotification($detector);
        return $view->render();
    }

    public function isRecommended(SiteContentDetector $detector): bool
    {
        return false; // do not recommend this, as it's used as fall back
    }

    public function getRecommendationDetails(SiteContentDetector $detector): array
    {
        $details = parent::getRecommendationDetails($detector);
        $details['text'] = Matomo::translate('SitesManager_SetupMatomoTracker');
        return $details;
    }

    private function getConsentManagerNotification(SiteContentDetector $detector): string
    {
        $consentManagers = $detector->getDetectsByType(SiteContentDetectionAbstract::TYPE_CONSENT_MANAGER);

        if (empty($consentManagers)) {
            return '';
        }

        $consentManagerId = reset($consentManagers);
        $consentManager = $detector->getSiteContentDetectionById($consentManagerId);

        if (empty($consentManager)) {
            return '';
        }

        $consentManagerName = $consentManager::getName();
        $consentManagerUrl = $consentManager::getInstructionUrl();
        $consentManagerIsConnected = in_array($consentManagerId, $detector->connectedConsentManagers);
        $notificationMessage = '<p>' . Matomo::translate('PrivacyManager_ConsentManagerDetected', [$consentManagerName, Url::getExternalLinkTag($consentManagerUrl), '</a>']) . '</p>';

        if (!empty($consentManagerIsConnected)) {
            $notificationMessage .= '<p>' . Matomo::translate('SitesManager_ConsentManagerConnected', [$consentManagerName]) . '</p>';
        }

        return $notificationMessage;
    }
}
