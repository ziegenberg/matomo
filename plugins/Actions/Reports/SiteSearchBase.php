<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Reports;

use Matomo\Common;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Actions\Actions;
use Matomo\Url;

abstract class SiteSearchBase extends Base
{
    protected function init()
    {
        parent::init();
        $this->categoryId = 'General_Actions';
        $this->onlineGuideUrl = Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/site-search/');
    }

    public function isEnabled()
    {
        $idSites = Common::getRequestVar('idSites', '', 'string');
        $idSite  = Common::getRequestVar('idSite', 0, 'int');

        return $this->isEnabledForIdSites($idSites, $idSite);
    }

    protected function isEnabledForIdSites($idSites, $idSite)
    {
        $actions = new Actions();
        return $actions->isSiteSearchEnabled($idSites, $idSite);
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        $idSite = array($infos['idSite']);

        if (!$this->isEnabledForIdSites($idSite, Common::getRequestVar('idSite', 0, 'int'))) {
            return;
        }

        $report = $this->buildReportMetadata();

        if (!empty($report)) {
            $availableReports[] = $report;
        }
    }

    protected function addSiteSearchDisplayProperties(ViewDataTable $view)
    {
        $view->config->addTranslations(array(
            'nb_visits'           => Matomo::translate('Actions_ColumnSearches'),
            'exit_rate'           => str_replace("% ", "%&nbsp;", Matomo::translate('Actions_ColumnSearchExits')),
            'nb_pages_per_search' => Matomo::translate('Actions_ColumnPagesPerSearch'),
        ));

        $view->config->show_bar_chart         = false;
        $view->config->show_table_all_columns = false;
    }
}
