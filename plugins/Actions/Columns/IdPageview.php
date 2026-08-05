<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Actions\Columns;

use Matomo\Columns\DimensionMetricFactory;
use Matomo\Columns\MetricsList;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;
use Matomo\Plugin\Dimension\ActionDimension;

class IdPageview extends ActionDimension
{
    protected $columnName = 'idpageview';
    protected $columnType = 'CHAR(6) NULL DEFAULT NULL';
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Actions_ColumnIdPageview';

    /**
     * @return mixed|false
     * @api
     */
    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        return substr($request->getParam('pv_id'), 0, 6);
    }

    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        // metrics for idpageview do not really make any sense
    }
}
