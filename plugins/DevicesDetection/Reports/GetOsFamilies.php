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
use Matomo\Plugins\DevicesDetection\Columns\Os;
use Matomo\Plugin\ReportsProvider;

class GetOsFamilies extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Os();
        $this->name          = Matomo::translate('DevicesDetection_OperatingSystemFamilies');
        $this->documentation = Matomo::translate('DevicesDetection_OperatingSystemFamiliesReportDocumentation');
        $this->order = 8;

        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->title = $this->name;
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('DevicesDetection', 'getOsVersions'),
        );
    }
}
