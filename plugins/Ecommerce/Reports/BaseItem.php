<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Ecommerce\Reports;

use Matomo\Common;
use Matomo\Metrics\Formatter;
use Matomo\Matomo;
use Matomo\Plugin\ViewDataTable;
use Matomo\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Matomo\Plugins\Goals\Columns\Metrics\AveragePrice;
use Matomo\Plugins\Goals\Columns\Metrics\AverageQuantity;
use Matomo\Plugins\Goals\Columns\Metrics\ProductConversionRate;
use Matomo\Plugins\Goals\Conversions;
use Matomo\Report\ReportWidgetFactory;
use Matomo\Widget\WidgetsList;

abstract class BaseItem extends Base
{
    protected $defaultSortColumn = 'revenue';

    protected function init()
    {
        parent::init();
        $this->processedMetrics = array(
            new AveragePrice(),
            new AverageQuantity(),
            new ProductConversionRate(),
        );
        $this->metrics = array(
            'revenue', 'quantity', 'orders', 'nb_visits',
        );
    }

    public function getMetrics()
    {
        $metrics = parent::getMetrics();
        $metrics['revenue'] = Matomo::translate('General_ProductRevenue');
        $metrics['orders']  = Matomo::translate('General_UniquePurchases');
        return $metrics;
    }

    public function getMetricsDocumentation()
    {
        if ($this->isAbandonedCart(false)) {
            return [
                'revenue'         => Matomo::translate('Goals_ColumnRevenueLeftInCartDocumentation'),
                'quantity'        => Matomo::translate('Goals_ColumnQuantityLeftInCartDocumentation', $this->name),
                'avg_price'       => Matomo::translate('Goals_ColumnAveragePriceDocumentation', $this->name),
                'avg_quantity'    => Matomo::translate('Goals_ColumnAverageQuantityDocumentation', $this->name),
                'nb_visits'       => Matomo::translate('Goals_ColumnVisitsProductDocumentation', $this->name),
                'abandoned_carts' => Matomo::translate('Goals_ColumnAbandonedCartsDocumentation', $this->name),
            ];
        } else {
            return [
                'revenue'         => Matomo::translate(
                    'Goals_ColumnRevenueDocumentation',
                    Matomo::translate('Goals_DocumentationRevenueGeneratedByProductSales')
                ),
                'quantity'        => Matomo::translate('Goals_ColumnQuantityDocumentation', $this->name),
                'orders'          => Matomo::translate('Goals_ColumnOrdersDocumentation', $this->name),
                'avg_price'       => Matomo::translate('Goals_ColumnAveragePriceDocumentation', $this->name),
                'avg_quantity'    => Matomo::translate('Goals_ColumnAverageQuantityDocumentation', $this->name),
                'nb_visits'       => Matomo::translate('Goals_ColumnVisitsProductDocumentation', $this->name),
                'conversion_rate' => Matomo::translate('Goals_ColumnConversionRateProductDocumentation', $this->name),
            ];
        }
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widgetsList->addToContainerWidget('Products', $factory->createWidget());
    }

    public function configureView(ViewDataTable $view)
    {
        $idSite = Common::getRequestVar('idSite');

        $view->config->show_ecommerce = true;
        $view->config->show_table     = false;
        $view->config->show_all_views_icons      = false;
        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns      = false;

        if (!($view instanceof Evolution)) {
            $moneyColumns = array('revenue');
            $formatter    = array(new Formatter(), 'getPrettyMoney');
            $view->config->filters[] = array('ColumnCallbackReplace', array($moneyColumns, $formatter, array($idSite)));
        }

        $view->requestConfig->filter_limit       = 10;
        $view->requestConfig->filter_sort_column = 'revenue';
        $view->requestConfig->filter_sort_order  = 'desc';

        $view->config->custom_parameters['isFooterExpandedInDashboard'] = true;

        // set columns/translations which differ based on viewDataTable TODO: shouldn't have to do this check...
        // amount of reports should be dynamic, but metadata should be static
        $columns = array_merge($this->getMetrics(), $this->getProcessedMetrics());
        $columnsOrdered = array('label', 'revenue', 'quantity', 'orders', 'avg_price', 'avg_quantity',
                                'nb_visits', 'conversion_rate');

        // handle old case where viewDataTable is set to ecommerceOrder/ecommerceAbandonedCart. in this case, we
        // set abandonedCarts accordingly and remove the ecommerceOrder/ecommerceAbandonedCart as viewDataTable.
        $viewDataTable = Common::getRequestVar('viewDataTable', '');
        if ($viewDataTable == 'ecommerceOrder') {
            $view->config->custom_parameters['viewDataTable'] = 'table';
            $abandonedCart = false;
        } elseif ($viewDataTable == 'ecommerceAbandonedCart') {
            $view->config->custom_parameters['viewDataTable'] = 'table';
            $abandonedCart = true;
        } else {
            $abandonedCart = $this->isAbandonedCart($fetchIfNotSet = true);
        }

        if ($abandonedCart) {
            $columns['abandoned_carts'] = Matomo::translate('General_AbandonedCarts');
            $columns['revenue'] = Matomo::translate('Goals_LeftInCart', $columns['revenue']);
            $columns['quantity'] = Matomo::translate('Goals_LeftInCart', $columns['quantity']);
            $columns['avg_quantity'] = Matomo::translate('Goals_LeftInCart', $columns['avg_quantity']);
            unset($columns['orders']);
            unset($columns['conversion_rate']);

            $columnsOrdered = array('label', 'revenue', 'quantity', 'avg_price', 'avg_quantity', 'nb_visits',
                                    'abandoned_carts');

            $view->config->custom_parameters['abandonedCarts'] = '1';
        } else {
            $view->config->custom_parameters['abandonedCarts'] = '0';
        }

        $view->requestConfig->request_parameters_to_modify['abandonedCarts'] = $view->config->custom_parameters['abandonedCarts'];

        $translations = array_merge(array('label' => $this->name), $columns);

        $view->config->addTranslations($translations);
        $view->config->columns_to_display = $columnsOrdered;
    }

    private function isAbandonedCart($fetchIfNotSet)
    {
        $abandonedCarts = Common::getRequestVar('abandonedCarts', '', 'string');

        if ($abandonedCarts === '') {
            if ($fetchIfNotSet) {
                $idSite = Common::getRequestVar('idSite', 0, 'int');
                $period = Common::getRequestVar('period', '', 'string');
                $date   = Common::getRequestVar('date', '', 'string');

                $conversion = new Conversions();
                $conversions = $conversion->getConversionForGoal(Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_ORDER, $idSite, $period, $date);
                $cartNbConversions = $conversion->getConversionForGoal(Matomo::LABEL_ID_GOAL_IS_ECOMMERCE_CART, $idSite, $period, $date);
                $preloadAbandonedCart = $cartNbConversions !== false && $conversions == 0;

                if ($preloadAbandonedCart) {
                    $abandonedCarts = '1';
                } else {
                    $abandonedCarts = '0';
                }
            } else {
                $abandonedCarts = '0';
            }
        }

        return $abandonedCarts == '1';
    }
}
