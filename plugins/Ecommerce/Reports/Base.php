<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin\Report;
use Matomo\Url;
use Matomo\Site;

abstract class Base extends Report
{
    protected function init()
    {
        $this->module   = 'Goals';
        $this->categoryId = 'Goals_Ecommerce';
        $this->onlineGuideUrl = Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/ecommerce-analytics/');
    }

    public function isEnabled()
    {
        $idSite = Common::getRequestVar('idSite', false, 'int');

        if (empty($idSite)) {
            return false;
        }

        return $this->isEcommerceEnabled($idSite);
    }

    public function checkIsEnabled()
    {
        if (!$this->isEnabled()) {
            $message = Matomo::translate('General_ExceptionReportNotEnabled');

            if (Matomo::hasUserSuperUserAccess()) {
                $message .= ' Most likely Ecommerce is not enabled for the requested site.';
            }

            throw new \Exception($message);
        }
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        if ($this->isEcommerceEnabledByInfos($infos)) {
            $availableReports[] = $this->buildReportMetadata();
        }
    }

    private function isEcommerceEnabledByInfos(array $infos): bool
    {
        $idSite = $infos['idSite'];

        if (empty($idSite) || !is_numeric($idSite)) {
            return false;
        }

        return $this->isEcommerceEnabled((int) $idSite);
    }

    private function isEcommerceEnabled(int $idSite): bool
    {
        $site = new Site($idSite);
        return $site->isEcommerceEnabled();
    }
}
