<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\UserCountry\Columns\Region;

class GetRegion extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension      = new Region();
        $this->name           = Matomo::translate('UserCountry_Region');
        $this->documentation  = Matomo::translate('UserCountry_getRegionDocumentation') . '<br/>' . $this->getGeoIPReportDocSuffix();
        $this->order = 7;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->show_exclude_low_population = false;
        $view->config->documentation = $this->documentation;

        $view->requestConfig->filter_limit = 5;

        $this->checkIfNoDataForGeoIpReport($view);
    }
}
