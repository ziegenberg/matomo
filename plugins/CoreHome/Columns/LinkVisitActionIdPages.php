<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\DimensionSegmentFactory;
use Matomo\Columns\Discriminator;
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Segment\SegmentsList;
use Matomo\Tracker\Action;

class LinkVisitActionIdPages extends ActionDimension
{
    protected $columnName = 'idlink_va';
    protected $category = 'General_Actions';
    protected $nameSingular = 'General_Actions';
    protected $type = self::TYPE_NUMBER;

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        // empty so we don't auto-generate a segment
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_PAGE_URL);
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_UNIQUE);
        $metric->setTranslatedName(Matomo::translate('General_ColumnPageviews'));
        $metric->setDocumentation(Matomo::translate('General_ColumnPageviewsDocumentation'));
        $metric->setName('pageviews');
        $metricsList->addMetric($metric);
    }
}
