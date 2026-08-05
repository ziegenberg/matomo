<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\VisitorInterest\RecordBuilders;

use Matomo\ArchiveProcessor;
use Matomo\ArchiveProcessor\Record;
use Matomo\ArchiveProcessor\RecordBuilder;
use Matomo\DataAccess\LogAggregator;
use Matomo\DataTable;
use Matomo\Metrics;
use Matomo\Plugins\VisitorInterest\Archiver;

class Engagement extends RecordBuilder
{
    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            Record::make(Record::TYPE_BLOB, Archiver::TIME_SPENT_RECORD_NAME),
            Record::make(Record::TYPE_BLOB, Archiver::PAGES_VIEWED_RECORD_NAME),
            Record::make(Record::TYPE_BLOB, Archiver::VISITS_COUNT_RECORD_NAME),
            Record::make(Record::TYPE_BLOB, Archiver::DAYS_SINCE_LAST_RECORD_NAME),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        // these prefixes are prepended to the 'SELECT as' parts of each SELECT expression. detecting
        // these prefixes allows us to get all the data in one query.
        $prefixes = [
            Archiver::TIME_SPENT_RECORD_NAME      => 'tg',
            Archiver::PAGES_VIEWED_RECORD_NAME    => 'pg',
            Archiver::VISITS_COUNT_RECORD_NAME    => 'vbvn',
            Archiver::DAYS_SINCE_LAST_RECORD_NAME => 'dslv',
        ];

        // collect our extra aggregate select fields
        $selects = array();
        $selects = array_merge($selects, LogAggregator::getSelectsFromRangedColumn(
            'visit_total_time',
            Archiver::getSecondsGap(),
            'log_visit',
            $prefixes[Archiver::TIME_SPENT_RECORD_NAME]
        ));
        $selects = array_merge($selects, LogAggregator::getSelectsFromRangedColumn(
            'visit_total_actions',
            Archiver::$pageGap,
            'log_visit',
            $prefixes[Archiver::PAGES_VIEWED_RECORD_NAME]
        ));
        $selects = array_merge($selects, LogAggregator::getSelectsFromRangedColumn(
            'visitor_count_visits',
            Archiver::$visitNumberGap,
            'log_visit',
            $prefixes[Archiver::VISITS_COUNT_RECORD_NAME]
        ));

        $selects = array_merge($selects, LogAggregator::getSelectsFromRangedColumn(
            'FLOOR(log_visit.visitor_seconds_since_last / 86400)',
            Archiver::$daysSinceLastVisitGap,
            'log_visit',
            $prefixes[Archiver::DAYS_SINCE_LAST_RECORD_NAME],
            $restrictToReturningVisitors = true
        ));

        $records = [];

        $query = $archiveProcessor->getLogAggregator()->queryVisitsByDimension(array(), $where = false, $selects, array());
        $row = $query->fetch();
        foreach ($prefixes as $recordName => $selectAsPrefix) {
            $cleanRow = LogAggregator::makeArrayOneColumn($row, Metrics::INDEX_NB_VISITS, $selectAsPrefix);
            $records[$recordName] = DataTable::makeFromIndexedArray($cleanRow);
        }

        return $records;
    }
}
