<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Referrers\Columns\WebsitePage;

class GetUrlsForSocial extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new WebsitePage();
        $this->name          = Matomo::translate('Referrers_Socials');
        $this->documentation = Matomo::translate('Referrers_WebsitesReportDocumentation', '<br />');
        $this->isSubtableReport = true;
        $this->order = 12;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_goals = true;
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 10;
    }
}
