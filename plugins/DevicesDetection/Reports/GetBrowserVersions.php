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
use Matomo\Plugins\DevicesDetection\Columns\BrowserVersion;
use Matomo\Plugin\ReportsProvider;

class GetBrowserVersions extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new BrowserVersion();
        $this->name          = Matomo::translate('DevicesDetection_BrowserVersion');
        $this->documentation = Matomo::translate('DevicesDetection_WidgetBrowserVersionsDocumentation');
        $this->order = 6;
        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = true;
        $view->config->show_exclude_low_population = false;
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('DevicesDetection', 'getBrowsers'),
        );
    }
}
