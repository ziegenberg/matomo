<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PagePerformance;

use Matomo\Columns\Dimension;
use Matomo\Plugins\PagePerformance\Columns\Base;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AveragePageLoadTime;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomCompletion;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomProcessing;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeNetwork;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeServer;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeOnLoad;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeTransfer;
use Matomo\Plugins\PagePerformance\Columns\TimeDomCompletion;
use Matomo\Plugins\PagePerformance\Columns\TimeDomProcessing;
use Matomo\Plugins\PagePerformance\Columns\TimeNetwork;
use Matomo\Plugins\PagePerformance\Columns\TimeServer;
use Matomo\Plugins\PagePerformance\Columns\TimeOnLoad;
use Matomo\Plugins\PagePerformance\Columns\TimeTransfer;

class Metrics
{
    /**
     * @return \Matomo\Plugins\PagePerformance\Columns\Metrics\AveragePerformanceMetric[]
     */
    public static function getPagePerformanceMetrics()
    {
        $metrics = [
            new AverageTimeNetwork(),
            new AverageTimeServer(),
            new AverageTimeTransfer(),
            new AverageTimeDomProcessing(),
            new AverageTimeDomCompletion(),
            new AverageTimeOnLoad(),
        ];

        $mappedMetrics = [];

        foreach ($metrics as $metric) {
            $mappedMetrics[$metric->getName()] = $metric;
        }

        return $mappedMetrics;
    }

    /**
     * @return \Matomo\Plugins\PagePerformance\Columns\Metrics\AveragePerformanceMetric[]
     */
    public static function getAllPagePerformanceMetrics()
    {
        $metrics = [
            new AverageTimeNetwork(),
            new AverageTimeServer(),
            new AverageTimeTransfer(),
            new AverageTimeDomProcessing(),
            new AverageTimeDomCompletion(),
            new AverageTimeOnLoad(),
            new AveragePageLoadTime(),
        ];

        $mappedMetrics = [];

        foreach ($metrics as $metric) {
            $mappedMetrics[$metric->getName()] = $metric;
        }

        return $mappedMetrics;
    }

    public static function getMetricTranslations()
    {
        $translations = array();
        foreach (self::getAllPagePerformanceMetrics() as $metric) {
            $translations[$metric->getName()] = $metric->getTranslatedName();
        }

        return $translations;
    }

    public static function getMetricSemanticTypes()
    {
        $types = [];
        foreach (self::getAllPagePerformanceMetrics() as $metric) {
            $types[$metric->getName()] = Dimension::TYPE_DURATION_S;
        }
        return $types;
    }

    public static function attachActionMetrics(&$metricsConfig)
    {
        $table = 'log_link_visit_action';

        /**
         * @var Base[] $performanceDimensions
         */
        $performanceDimensions = [
            new TimeNetwork(),
            new TimeServer(),
            new TimeTransfer(),
            new TimeDomProcessing(),
            new TimeDomCompletion(),
            new TimeOnLoad(),
        ];
        foreach ($performanceDimensions as $dimension) {
            $id = $dimension->getColumnName();
            $column = $table . '.' . $id;
            $metricsConfig['sum_' . $id] = [
                'aggregation' => 'sum',
                'query' => "sum(" . sprintf($dimension->getSqlCappedValue(), $column) . ") / 1000",
            ];
            $metricsConfig['nb_hits_with_' . $id] = [
                'aggregation' => 'sum',
                'query' => "sum(
                    case when " . $column . " is null
                        then 0
                        else 1
                    end
                )",
            ];
            $metricsConfig['min_' . $id] = [
                'aggregation' => 'min',
                'query' => "min(" . $column . ") / 1000",
            ];
            $metricsConfig['max_' . $id] = [
                'aggregation' => 'max',
                'query' => "max(" . $column . ") / 1000",
            ];
        }

        return $metricsConfig;
    }
}
