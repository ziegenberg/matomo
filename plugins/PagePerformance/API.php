<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PagePerformance;

use Matomo\Archive;
use Matomo\DataTable;
use Matomo\Matomo;
use Matomo\Plugin\ProcessedMetric;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AveragePageLoadTime;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomCompletion;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomProcessing;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeNetwork;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeServer;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeOnLoad;
use Matomo\Plugins\PagePerformance\Columns\Metrics\AverageTimeTransfer;

/**
 * Provides reporting API methods for aggregated page performance metrics.
 *
 * @method static \Matomo\Plugins\PagePerformance\API getInstance()
 */
class API extends \Matomo\Plugin\API
{
    /**
     * Returns aggregated page performance metrics for the requested site and period.
     *
     * @param int|string|int[] $idSite Website ID(s) to query.
     *                                 - Single site ID (e.g. 1)
     *                                 - Multiple site IDs (e.g. [1, 4, 5])
     *                                 - Comma-separated list ("1,4,5") or "all"
     * @param 'day'|'week'|'month'|'year'|'range' $period The period to process, processes data for the period
     *                                                   containing the specified date.
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param string|null|false $segment Custom segment to filter the report.
     *                                   Example: "referrerName==example.com"
     *                                   Supports AND (;) and OR (,) operators.
     * @return DataTable|DataTable\Map Metrics for network, server, transfer, DOM, and page load timings.
     */
    public function get($idSite, string $period, string $date, $segment = false)
    {
        Matomo::checkUserHasViewAccess($idSite);

        $archive = Archive::build($idSite, $period, $date, $segment);

        $columns = [
            Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_SERVER_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_HITS,
            Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_HITS,
        ];

        $dataTable = $archive->getDataTableFromNumeric($columns);

        $precision = 2;

        $dataTable->filter('ColumnCallbackReplace', [
            [
                Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME,
                Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME,
            ],
            function ($value) {
                return $value / 1000;
            },
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeNetwork::class),
            Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeServer::class),
            Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_SERVER_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeTransfer::class),
            Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeDomProcessing::class),
            Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeDomCompletion::class),
            Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AverageTimeOnLoad::class),
            Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_HITS,
            $precision,
        ]);

        $dataTable->filter('ColumnCallbackAddColumnQuotient', [
            $this->getMetricColumn(AveragePageLoadTime::class),
            Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME,
            Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_HITS,
            $precision,
        ]);

        $dataTable->queueFilter('ColumnDelete', [$columns]);

        return $dataTable;
    }

    /**
     * @param class-string<ProcessedMetric> $class
     */
    private function getMetricColumn(string $class): string
    {
        /** @var ProcessedMetric $metric */
        $metric = new $class();
        return $metric->getName();
    }
}
