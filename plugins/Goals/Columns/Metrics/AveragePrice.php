<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Goals\Columns\Metrics;

use Matomo\Archive\DataTableFactory;
use Matomo\Columns\Dimension;
use Matomo\DataTable;
use Matomo\DataTable\Row;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Tracker\GoalManager;

/**
 * The average price for each ecommerce order or abandoned cart. Calculated as:
 *
 *     price / (orders or abandoned_carts)
 *
 * price, orders and abandoned_carts are calculated by the Goals archiver.
 */
class AveragePrice extends ProcessedMetric
{
    private $idSite;

    public function getName()
    {
        return 'avg_price';
    }

    public function getTranslatedName()
    {
        return Matomo::translate('General_AveragePrice');
    }

    public function compute(Row $row)
    {
        $price = $this->getMetric($row, 'price');
        $orders = $this->getMetric($row, 'orders');
        $abandonedCarts = $this->getMetric($row, 'abandoned_carts');

        return Matomo::getQuotientSafe($price, $orders === false ? $abandonedCarts : $orders, GoalManager::REVENUE_PRECISION);
    }

    public function getDependentMetrics()
    {
        return array('price', 'orders', 'abandoned_carts');
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyMoney($value, $this->idSite);
    }

    public function beforeFormat($report, DataTable $table)
    {
        $this->idSite = DataTableFactory::getSiteIdFromMetadata($table);
        return !empty($this->idSite); // skip formatting if there is no site to get currency info from
    }

    public function getSemanticType(): ?string
    {
        return Dimension::TYPE_MONEY;
    }
}
