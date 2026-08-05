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

class TimeDomProcessing extends Base
{
    public const COLUMN_TYPE = 'MEDIUMINT(10) UNSIGNED NULL';
    public const COLUMN_NAME = 'time_dom_processing';

    protected $columnName = self::COLUMN_NAME;
    protected $columnType = self::COLUMN_TYPE;
    protected $nameSingular = 'PagePerformance_ColumnTimeDomProcessing';

    public function getRequestParam()
    {
        return 'pf_dm1';
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric1 = $dimensionMetricFactory->createMetric('sum(' . $this->getSqlCappedValue() . ')');
        $metric1->setName('sum_time_dom_processing');
        $metricsList->addMetric($metric1);

        $metric2 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MAX);
        $metric2->setName('max_time_dom_processing');
        $metricsList->addMetric($metric2);

        $metric3 = $dimensionMetricFactory->createMetric('sum(if(%s is null, 0, 1))');
        $metric3->setName('pageviews_with_time_dom_processing');
        $metric3->setType(self::TYPE_NUMBER);
        $metric3->setTranslatedName(Matomo::translate('PagePerformance_ColumnViewsWithTimeDomProcessing'));
        $metricsList->addMetric($metric3);

        $metric4 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MIN);
        $metric4->setName('min_time_dom_processing');
        $metricsList->addMetric($metric4);

        $metric = $dimensionMetricFactory->createComputedMetric($metric1->getName(), $metric3->getName(), ComputedMetric::AGGREGATION_AVG);
        $metric->setName('avg_time_dom_processing');
        $metric->setTranslatedName(Matomo::translate('PagePerformance_ColumnAverageTimeDomProcessing'));
        $metric->setDocumentation(Matomo::translate('PagePerformance_ColumnAverageTimeDomProcessingDocumentation'));
        $metricsList->addMetric($metric);
    }
}
