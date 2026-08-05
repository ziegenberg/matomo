<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Columns\MetricsList;
use Matomo\Matomo;
use Matomo\Plugin\ArchivedMetric;
use Matomo\Plugin\ComputedMetric;
use Matomo\Plugin\Dimension\VisitDimension;
use Matomo\Columns\DimensionMetricFactory;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

class VisitTotalActions extends VisitDimension
{
    protected $columnName = 'visit_total_actions';
    protected $columnType = 'INT(11) UNSIGNED NULL';
    protected $metricId = 'actions';
    protected $nameSingular = 'Actions_ActionsInVisit';
    protected $segmentName = 'actions';
    protected $type = self::TYPE_NUMBER;

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric1 = $dimensionMetricFactory->createCustomMetric('bounce_count', Matomo::translate('General_ColumnBounces'), 'sum(case %s when 1 then 1 when 0 then 1 else 0 end)');
        $metric1->setDocumentation(Matomo::translate('General_ColumnBouncesDocumentation'));
        $metricsList->addMetric($metric1);

        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_SUM);
        $metricsList->addMetric($metric);

        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MAX);
        $metricsList->addMetric($metric);

        $metric = $dimensionMetricFactory->createComputedMetric($metric1->getName(), 'nb_visits', ComputedMetric::AGGREGATION_RATE);
        $metric->setTranslatedName(Matomo::translate('General_ColumnBounceRate'));
        $metric->setName('bounce_rate');
        $metric->setDocumentation(Matomo::translate('General_ColumnBounceRateDocumentation'));
        $metricsList->addMetric($metric);
    }

    /**
     * @param Action|null $action
     * @return int
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $actionType = false;
        if ($action) {
            $actionType = $action->getActionType();
        }

        $actions = array(
            Action::TYPE_PAGE_URL,
            Action::TYPE_DOWNLOAD,
            Action::TYPE_OUTLINK,
            Action::TYPE_SITE_SEARCH,
            Action::TYPE_EVENT,
        );

        // if visit starts with something else (e.g. ecommerce order), don't record as an action
        if (in_array($actionType, $actions)) {
            return 1;
        }

        return 0;
    }

    /**
     * @param Action|null $action
     * @return string|false
     */
    public function onExistingVisit(Request $request, Visitor $visitor, $action)
    {
        if (!$action) {
            return false;
        }

        $increment = 'visit_total_actions + 1';

        $idActionUrl = $action->getIdActionUrlForEntryAndExitIds();

        if ($idActionUrl !== false) {
            return $increment;
        }

        $actionType = $action->getActionType();
        $types = array(Action::TYPE_SITE_SEARCH, Action::TYPE_EVENT, Action::TYPE_OUTLINK, Action::TYPE_DOWNLOAD);

        if (in_array($actionType, $types)) {
            return $increment;
        }

        return false;
    }
}
