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
use Matomo\Plugins\DevicesDetection\Columns\DeviceType;

class GetType extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new DeviceType();
        $this->name          = Matomo::translate('DevicesDetection_DeviceType');
        $this->documentation = Matomo::translate('DevicesDetection_DeviceTypeReportDocumentation');
        $this->order = 0;
        $this->hasGoalMetrics = true;
        $this->subcategoryId = 'DevicesDetection_Devices';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_flatten_table = false;
        $view->config->show_flatten_table_export = false;
        $view->config->show_search = true;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', Matomo::translate("DevicesDetection_dataTableLabelTypes"));
    }
}
