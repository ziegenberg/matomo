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

class GetKeywordsFromSearchEngineId extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Keyword();
        $this->name          = Matomo::translate('Referrers_SearchEngines');
        $this->documentation = Matomo::translate('Referrers_SearchEnginesReportDocumentation', '<br />');
        $this->isSubtableReport = true;
        $this->order = 8;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;
    }
}
