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
use Matomo\Policy\CnilPolicy;
use Matomo\Plugins\Resolution\Columns\Resolution;
use Matomo\Plugin\ReportsProvider;
use Matomo\Policy\PolicyManager;

class GetResolution extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Resolution();
        $this->name          = Matomo::translate('Resolution_WidgetResolutions');
        $this->documentation = Matomo::translate('Resolution_WidgetResolutionsDocumentation');
        $this->order = 8;

        $this->subcategoryId = 'DevicesDetection_Devices';
    }

    public function configureView(ViewDataTable $view)
    {
        $this->getBasicResolutionDisplayProperties($view);
    }

    public function getRelatedReports()
    {
        return array(
            ReportsProvider::factory('Resolution', 'getConfiguration'),
        );
    }

    public function isEnabled()
    {
        // Metadata visibility is global-only here, so check the policy state directly.
        return !PolicyManager::isPolicyActive(CnilPolicy::class, $idSite = null);
    }
}
