<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Columns;

use Matomo\Common;
use Matomo\Exception\InvalidRequestParameterException;
use Matomo\Network\IPUtils;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Plugins\UserCountry\VisitorGeolocator;
use Matomo\Plugins\PrivacyManager\Config as PrivacyManagerConfig;
use Matomo\Tracker\Visitor;
use Matomo\Tracker\Request;

abstract class Base extends VisitDimension
{
    /**
     * @var VisitorGeolocator
     */
    private $visitorGeolocator;

    protected function getUrlOverrideValueIfAllowed($urlParamToOverride, Request $request)
    {
        return self::getValueFromUrlParamsIfAllowed($urlParamToOverride, $request);
    }

    public static function getValueFromUrlParamsIfAllowed($urlParamToOverride, Request $request)
    {
        $value = Common::getRequestVar($urlParamToOverride, false, 'string', $request->getParams());

        if (!empty($value)) {
            if (!$request->isAuthenticated()) {
                Common::printDebug("WARN: Tracker API '$urlParamToOverride' was used with invalid token_auth");
                throw new InvalidRequestParameterException("Tracker API '$urlParamToOverride' was used, requires valid token_auth");
            }
            return $value;
        }

        return false;
    }

    public function getRequiredVisitFields()
    {
        return array('location_ip', 'location_browser_lang');
    }

    protected function getLocationDetail($userInfo, $locationKey)
    {
        $useLocationCache = empty($GLOBALS['PIWIK_TRACKER_LOCAL_TRACKING']);
        $location = $this->getVisitorGeolocator()->getLocation($userInfo, $useLocationCache);

        if (!isset($location[$locationKey])) {
            return false;
        }

        return $location[$locationKey];
    }

    protected function getVisitorGeolocator()
    {
        if ($this->visitorGeolocator === null) {
            $this->visitorGeolocator = new VisitorGeolocator();
        }

        return $this->visitorGeolocator;
    }

    protected function getUserInfo(Request $request, Visitor $visitor)
    {
        $ipAddress = $this->getIpAddress($visitor->getVisitorColumn('location_ip'), $request);
        $language  = $request->getBrowserLanguage();

        $userInfo  = array('lang' => $language, 'ip' => $ipAddress);

        return $userInfo;
    }

    private function getIpAddress($anonymizedIp, \Matomo\Tracker\Request $request)
    {
        $privacyConfig = new PrivacyManagerConfig();

        $ip = $request->getIp();

        if ($privacyConfig->useAnonymizedIpForVisitEnrichment) {
            $ip = $anonymizedIp;
        }

        $ipAddress = IPUtils::binaryToStringIP($ip);

        return $ipAddress;
    }
}
