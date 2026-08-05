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
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Pie;
use Matomo\Plugins\DevicesDetection\Columns\BrowserEngine;

class GetBrowserEngines extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new BrowserEngine();
        $this->name          = Matomo::translate('DevicesDetection_BrowserEngines');
        $this->documentation = Matomo::translate('DevicesDetection_BrowserEngineDocumentation', '<br />');
        $this->order = 10;

        $this->subcategoryId = 'DevicesDetection_Software';
    }

    public function getDefaultTypeViewDataTable()
    {
        return Pie::ID;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;
    }
}
