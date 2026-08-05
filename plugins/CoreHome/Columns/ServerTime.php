<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome\Columns;

use Matomo\Date;
use Matomo\Metrics\Formatter;
use Matomo\Plugin\Dimension\ActionDimension;
use Matomo\Tracker\Action;
use Matomo\Tracker\Request;
use Matomo\Tracker\Visitor;

require_once PIWIK_INCLUDE_PATH . '/plugins/VisitTime/functions.php';

class ServerTime extends ActionDimension
{
    protected $columnName = 'server_time';
    protected $columnType = 'DATETIME NOT NULL';
    protected $segmentName = 'actionServerHour';
    protected $sqlSegment = 'HOUR(log_link_visit_action.server_time)';
    protected $nameSingular = 'VisitTime_ColumnSiteHour';
    protected $type = self::TYPE_DATETIME;

    public function __construct()
    {
        $this->suggestedValuesCallback = function ($idSite, $maxValuesToReturn) {
            return range(0, min(23, $maxValuesToReturn));
        };
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        $hourInTz = VisitLastActionTime::convertHourToHourInSiteTimezone($value, $idSite);
        return \Matomo\Plugins\VisitTime\getTimeLabel($hourInTz);
    }

    public function install()
    {
        $changes = parent::install();
        $changes['log_link_visit_action'][] = "ADD INDEX index_idsite_servertime ( idsite, server_time )";

        return $changes;
    }

    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        $timestamp = $request->getCurrentTimestamp();

        return Date::getDatetimeFromTimestamp($timestamp);
    }
}
