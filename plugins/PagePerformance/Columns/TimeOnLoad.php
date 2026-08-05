<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PagePerformance\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\ComputedMetric;

class TimeOnLoad extends Base
{
    public const COLUMN_TYPE = 'MEDIUMINT(10) UNSIGNED NULL';
    public const COLUMN_NAME = 'time_on_load';

    protected $columnName = self::COLUMN_NAME;
    protected $columnType = self::COLUMN_TYPE;
    protected $nameSingular = 'PagePerformance_ColumnTimeOnLoad';

    public function getRequestParam()
    {
        return 'pf_onl';
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric1 = $dimensionMetricFactory->createMetric('sum(' . $this->getSqlCappedValue() . ')');
        $metric1->setName('sum_time_on_load');
        $metricsList->addMetric($metric1);

        $metric2 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MAX);
        $metric2->setName('max_time_on_load');
        $metricsList->addMetric($metric2);

        $metric3 = $dimensionMetricFactory->createMetric('sum(if(%s is null, 0, 1))');
        $metric3->setName('pageviews_with_time_on_load');
        $metric3->setType(self::TYPE_NUMBER);
        $metric3->setTranslatedName(Matomo::translate('PagePerformance_ColumnViewsWithTimeOnLoad'));
        $metricsList->addMetric($metric3);

        $metric4 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MIN);
        $metric4->setName('min_time_on_load');
        $metricsList->addMetric($metric4);

        $metric = $dimensionMetricFactory->createComputedMetric($metric1->getName(), $metric3->getName(), ComputedMetric::AGGREGATION_AVG);
        $metric->setName('avg_time_on_load');
        $metric->setTranslatedName(Matomo::translate('PagePerformance_ColumnAverageTimeOnLoad'));
        $metric->setDocumentation(Matomo::translate('PagePerformance_ColumnAverageTimeOnLoadDocumentation'));
        $metricsList->addMetric($metric);
    }
}
