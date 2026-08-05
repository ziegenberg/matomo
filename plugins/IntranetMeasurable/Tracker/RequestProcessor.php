<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\IntranetMeasurable\Tracker;

use Matomo\Container\StaticContainer;
use Matomo\Exception\UnexpectedWebsiteFoundException;
use Matomo\Plugins\IntranetMeasurable\Type;
use Matomo\Tracker\Cache;
use Matomo\Tracker\Request;

class RequestProcessor extends \Matomo\Tracker\RequestProcessor
{
    private $didEnableSetting = false;
    private $settingName = 'ini.Tracker.trust_visitors_cookies';

    public function manipulateRequest(Request $request)
    {
        try {
            $site = Cache::getCacheWebsiteAttributes($request->getIdSite());
        } catch (UnexpectedWebsiteFoundException $e) {
            return;
        }
        $isIntranetSite = !empty($site['type']) && $site['type'] === Type::ID;

        if ($isIntranetSite) {
            if (!StaticContainer::get($this->settingName)) {
                $this->setTrustCookiesSetting(1);
                $this->didEnableSetting = true;
            }
        } elseif ($this->didEnableSetting) {
            // reset it when a following (bulk-tracking) request is for a non-intranet site
            $this->setTrustCookiesSetting(0);
            $this->didEnableSetting = false;
        }
    }

    private function setTrustCookiesSetting($value)
    {
        StaticContainer::get('Matomo\Tracker\VisitorRecognizer')->setTrustCookiesOnly($value);
        StaticContainer::getContainer()->set($this->settingName, $value);
    }
}
