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
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;

/**
 * The average amount of products in each order or abandoned cart. Calculated as:
 *
 *     quantity / (orders or abandoned_carts)
 *
 * quantity, orders and abandoned_carts are calculated by the Goals archiver.
 */
class AverageQuantity extends ProcessedMetric
{
    public function getName()
    {
        return 'avg_quantity';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_AverageQuantity');
    }

    public function compute(Row $row)
    {
        $quantity = $this->getMetric($row, 'quantity');
        $orders = $this->getMetric($row, 'orders');
        $abandonedCarts = $this->getMetric($row, 'abandoned_carts');

        return Matomo::getQuotientSafe($quantity, $orders === false ? $abandonedCarts : $orders, $precision = 1);
    }

    public function getDependentMetrics()
    {
        return array('quantity', 'orders', 'abandoned_carts');
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_NUMBER;
    }
}
