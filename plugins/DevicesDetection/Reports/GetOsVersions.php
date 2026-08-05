<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicesDetection\Reports;

use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\DevicesDetection\Columns\OsVersion;
use Matomo\Plugin\ReportsProvider;

class GetOsVersions extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new OsVersion();
        $this->name          = Matomo::translate('DevicesDetection_OperatingSystemVersions');
        $this->documentation = Matomo::translate('DevicesDetection_OperatingSystemVersionsReportDocumentation');
        $this->order = 2;

        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->title = $this->name;
        $view->config->show_search = true;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', Matomo::translate("DevicesDetection_dataTableLabelSystemVersion"));
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('DevicesDetection', 'getOsFamilies'),
        );
    }
}
