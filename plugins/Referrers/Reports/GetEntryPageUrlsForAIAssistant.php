<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Matomo\Plugins\Referrers\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Actions\Columns\EntryPageUrl;

class GetEntryPageUrlsForAIAssistant extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new EntryPageUrl();
        $this->name = Matomo::translate('Referrers_AIAssistants');
        $this->isSubtableReport = true;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_goals                  = true;
        $view->config->show_exclude_low_population = false;

        $view->requestConfig->filter_limit = 10;
    }
}
