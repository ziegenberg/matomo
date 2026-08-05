<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\DevicePlugins\Columns;

use Matomo\Columns\Dimension;
use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\Dimension\VisitDimension;

/**
 * Columns extending this class will be automatically considered as new browser plugin
 *
 * Note: The column name needs to start with `config_` to be handled correctly
 */
abstract class DevicePluginColumn extends VisitDimension
{
    /**
     * Can be overwritten by Columns in other plugins to
     * set a custom icon not included in Piwik Core
     */
    public $columnIcon = null;

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $name = Matomo::translate('General_VisitsWith', [$this->getName()]);

        $metric = $dimensionMetricFactory->createCustomMetric('nb_visits_with_' . $this->getMetricId(), $name, 'sum(%s)');
        $metric->setType(Dimension::TYPE_NUMBER);
        $metricsList->addMetric($metric);
    }
}
