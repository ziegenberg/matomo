<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns\Metrics;

use Matomo\Columns\Dimension;
use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Tracker\GoalManager;

/**
 * The conversion rate for ecommerce orders. Calculated as:
 *
 *     (orders or abandoned_carts) / nb_visits
 *
 * orders and abandoned_carts are calculated by the Goals archiver.
 */
class ProductConversionRate extends ProcessedMetric
{
    public function getName()
    {
        return 'conversion_rate';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_ProductConversionRate');
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyPercentFromQuotient($value);
    }

    public function compute(Row $row)
    {
        $orders = $this->getMetric($row, 'orders');
        $abandonedCarts = $this->getMetric($row, 'abandoned_carts');
        $visits = $this->getMetric($row, 'nb_visits');

        return Matomo::getQuotientSafe($orders === false ? $abandonedCarts : $orders, $visits, GoalManager::REVENUE_PRECISION + 2);
    }

    public function getDependentMetrics()
    {
        return array('orders', 'abandoned_carts', 'nb_visits');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_PERCENT;
    }
}
