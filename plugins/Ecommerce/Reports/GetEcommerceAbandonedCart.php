<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Matomo;

class GetEcommerceAbandonedCart extends Base
{
    protected function init()
    {
        parent::init();
        $this->action = 'get';
        $this->name = Matomo::translate('General_AbandonedCarts');
        $this->processedMetrics = array('avg_order_revenue');
        $this->order = 15;
        $this->metrics = array('nb_conversions', 'conversion_rate', 'revenue', 'items');

        $this->parameters = array('idGoal' => Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_CART);
    }

    public function getMetrics()
    {
        $metrics = parent::getMetrics();

        $metrics['nb_conversions'] = Matomo::translate('General_AbandonedCarts');
        $metrics['revenue']        = Matomo::translate('Goals_LeftInCart', Matomo::translate('General_ColumnRevenue'));
        $metrics['items']          = Matomo::translate('Goals_LeftInCart', Matomo::translate('Goals_Products'));

        return $metrics;
    }
}
