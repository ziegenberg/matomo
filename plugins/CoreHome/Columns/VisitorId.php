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
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Plugins\Live\Live;
use Matomo\Segment\SegmentsList;
use Matomo\Columns\DimensionSegmentFactory;

/**
 * Dimension for the log_visit.idvisitor column. This column is added in the CREATE TABLE
 * statement, so this dimension exists only to configure a segment.
 */
class VisitorId extends VisitDimension
{
    protected $columnName = 'idvisitor';
    protected $metricId = 'visitors';
    protected $nameSingular = 'General_VisitorID';
    protected $namePlural = 'General_Visitors';
    protected $segmentName = 'visitorId';
    protected $allowAnonymous = false;
    protected $sqlFilterValue = ['Matomo\Common', 'convertVisitorIdToBin'];
    protected $type = self::TYPE_BINARY;

    public function getAcceptValues()
    {
        return Matomo::translate('General_VisitorIDSegmentHelp', ['34c31e04394bdc63', 'getVisitorId()']);
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_UNIQUE);
        $metric->setTranslatedName(Matomo::translate('General_ColumnNbUniqVisitors'));
        $metric->setName('nb_uniq_visitors');
        $metricsList->addMetric($metric);
    }

    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        try {
            $visitorProfileEnabled = Live::isVisitorProfileEnabled();
        } catch (\Zend_Db_Exception $e) {
            // when running tests the db might not yet be set up when fetching available segments
            if (!defined('PIWIK_TEST_MODE') || !PIWIK_TEST_MODE) {
                throw $e;
            }
            $visitorProfileEnabled = true;
        }

        if ($visitorProfileEnabled) {
            parent::configureSegments($segmentsList, $dimensionSegmentFactory);
        }
    }
}
