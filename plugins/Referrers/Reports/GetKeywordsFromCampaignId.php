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
use Matomo\Plugins\Referrers\Columns\Keyword;
use Matomo\Url;

class GetKeywordsFromCampaignId extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Keyword();
        $this->name          = Matomo::translate('Referrers_Campaigns');
        $this->documentation = Matomo::translate(
            'Referrers_CampaignsReportDocumentation',
            ['<br />', Url::getExternalLinkTag('https://matomo.org/docs/tracking-campaigns/'), '</a>']
        );
        $this->isSubtableReport = true;
        $this->order = 10;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;
    }
}
