<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Matomo;

class GetEcommerceOrder extends Base
{
    protected function init()
    {
        parent::init();
        $this->action = 'get';
        $this->name = Matomo::translate('General_EcommerceOrders');
        $this->processedMetrics = array('avg_order_revenue');
        $this->order = 10;
        $this->metrics = array(
            'nb_conversions',
            'nb_visits_converted',
            'conversion_rate',
            'revenue',
            'revenue_subtotal',
            'revenue_tax',
            'revenue_shipping',
            'revenue_discount',
        );

        $this->parameters = array('idGoal' => Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER);
    }

    public function getMetrics()
    {
        $metrics = parent::getMetrics();

        $metrics['nb_conversions'] = Matomo::translate('General_EcommerceOrders');
        $metrics['items']          = Matomo::translate('General_PurchasedProducts');

        return $metrics;
    }
}
