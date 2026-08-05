<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Resolution\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\Resolution\Columns\Configuration;
use Matomo\Plugin\ReportsProvider;

class GetConfiguration extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Configuration();
        $this->name          = Matomo::translate('Resolution_Configurations');
        $this->documentation = Matomo::translate('Resolution_WidgetGlobalVisitorsDocumentation', '<br />');
        $this->order = 7;

        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $this->getBasicResolutionDisplayProperties($view);

        $view->requestConfig->filter_limit = 3;
        $view->config->show_search = true;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Resolution', 'getResolution'),
        );
    }
}
