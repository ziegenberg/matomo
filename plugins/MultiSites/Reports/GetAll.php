<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MultiSites\Reports;

use Matomo\Matomo;
use Matomo\Plugins\MultiSites\Columns\Website;

class GetAll extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Website();
        $this->name          = Matomo::translate('General_AllWebsitesDashboard');
        $this->documentation = Matomo::translate('MultiSites_AllWebsitesDashboardDocumentation');
        $this->constantRowsCount = false;
        $this->order = 4;
    }
}
