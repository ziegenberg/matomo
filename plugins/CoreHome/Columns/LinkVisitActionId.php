<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\Dimension\ActionDimension;

class LinkVisitActionId extends ActionDimension
{
    protected $columnName = 'idlink_va';
    protected $acceptValues = 'General_AnyPositiveInteger';
    protected $category = 'General_Actions';
    protected $nameSingular = 'General_Actions';
    protected $metricId = 'hits';
    protected $type = self::TYPE_NUMBER;

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_UNIQUE);
        $metric->setTranslatedName(Matomo::translate('General_ColumnHits'));
        $metric->setName('hits');
        $metricsList->addMetric($metric);
    }
}
