<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Matomo;
use Matomo\Plugins\Goals\Columns\VisitsUntilConversion;

class GetVisitsUntilConversionAbandonedCart extends Base
{
    protected function init()
    {
        parent::init();

        $this->action = 'getVisitsUntilConversion';
        $this->name = Matomo::translate('General_AbandonedCarts') . ' - ' . Matomo::translate('Goals_VisitsUntilConv');
        $this->dimension = new VisitsUntilConversion();
        $this->constantRowsCount = true;
        $this->processedMetrics = [];
        $this->metrics = array('nb_conversions');
        $this->order = 20;

        $this->parameters =  array('idGoal' => Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_CART);
    }
}
