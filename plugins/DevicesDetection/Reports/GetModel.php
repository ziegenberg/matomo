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
use Matomo\Policy\CnilPolicy;
use Matomo\Plugins\DevicesDetection\Columns\DeviceModel;
use Matomo\Policy\PolicyManager;

class GetModel extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new DeviceModel();
        $this->name          = Matomo::translate('DevicesDetection_DeviceModel');
        $this->documentation = Matomo::translate('DevicesDetection_DeviceModelReportDocumentation');
        $this->order = 2;
        $this->hasGoalMetrics = true;
        $this->subcategoryId = 'DevicesDetection_Devices';
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = true;
        $view->config->show_exclude_low_population = false;
        $view->config->addTranslation('label', Matomo::translate("DevicesDetection_dataTableLabelModels"));
    }

    public function isEnabled()
    {
        // Metadata visibility is global-only here, so check the policy state directly.
        return !PolicyManager::isPolicyActive(CnilPolicy::class, $idSite = null);
    }
}
