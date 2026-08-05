<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\Discriminator;
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\ComputedMetric;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Tracker\Action;

/**
 * Page generation time has been deprecated in favor of new metrics in Page Performance plugin
 * It won't track any new values, but is still there to show the available data for the past
 *
 * @deprecated
 */
class PageGenerationTime extends ActionDimension
{
    protected $nameSingular = 'General_ColumnPageGenerationTime';
    protected $columnName = 'custom_float';
    protected $category = 'General_Actions';
    protected $type = self::TYPE_DURATION_MS;

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_PAGE_URL);
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric1 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_SUM);
        $metricsList->addMetric($metric1);

        $metric2 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MAX);
        $metricsList->addMetric($metric2);

        $metric3 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_COUNT_WITH_NUMERIC_VALUE);
        $metric3->setName('pageviews_with_generation_time');
        $metric3->setTranslatedName(Matomo::translate('General_ColumnViewsWithGenerationTime'));
        $metricsList->addMetric($metric3);

        $metric = $dimensionMetricFactory->createComputedMetric($metric1->getName(), $metric3->getName(), ComputedMetric::AGGREGATION_AVG);
        $metric->setName('avg_page_generation_time');
        $metric->setTranslatedName(Matomo::translate('General_ColumnAverageGenerationTime'));
        $metricsList->addMetric($metric);
    }
}
