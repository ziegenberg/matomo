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
use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Plugin\Segment;
use Matomo\Segment\SegmentsList;

/**
 * Dimension for the log_visit.idvisit column. This column is added in the CREATE TABLE
 * statement, so this dimension exists only to configure a segment.
 */
class VisitId extends VisitDimension
{
    protected $columnName = 'idvisit';
    protected $acceptValues = 'General_AnyPositiveInteger';
    protected $nameSingular = 'General_Visit';
    protected $namePlural = 'General_ColumnNbVisits';
    protected $segmentName = 'visitId';
    protected $allowAnonymous = false;
    protected $metricId = 'visits';
    protected $type = self::TYPE_TEXT;

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        $segment = new Segment();
        $segment->setName('General_VisitId');
        $segmentsList->addSegment($dimensionSegmentFactory->createSegment($segment));
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_UNIQUE);
        $metric->setTranslatedName(Matomo::translate('General_ColumnNbVisits'));
        $metric->setDocumentation(Matomo::translate('General_ColumnNbVisitsDocumentation'));
        $metric->setName('nb_visits');
        $metricsList->addMetric($metric);
    }
}
