<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserLanguage\RecordBuilders;

use Matomo\ArchiveProcessor;
use Matomo\ArchiveProcessor\Record;
use Matomo\ArchiveProcessor\RecordBuilder;
use Matomo\Common;
use Matomo\Config as PiwikConfig;
use Matomo\Container\StaticContainer;
use Matomo\DataTable;
use Matomo\Intl\Data\Provider\RegionDataProvider;
use Matomo\Metrics;
use Matomo\Plugins\UserLanguage\Archiver;

require_once PIWIK_INCLUDE_PATH . '/plugins/UserLanguage/functions.php';

class Languages extends RecordBuilder
{
    public function __construct()
    {
        parent::__construct();

        $this->maxRowsInTable = PiwikConfig::getInstance()->General['datatable_archiving_maximum_rows_standard'];
        $this->columnToSortByBeforeTruncation = Metrics::INDEX_NB_VISITS;
    }

    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            Record::make(Record::TYPE_BLOB, Archiver::LANGUAGE_RECORD_NAME),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        /** @var RegionDataProvider $regionDataProvider */
        $regionDataProvider = StaticContainer::get('Matomo\Intl\Data\Provider\RegionDataProvider');

        $query = $archiveProcessor->getLogAggregator()->queryVisitsByDimension(["label" => Archiver::LANGUAGE_DIMENSION]);
        $countryCodes = $regionDataProvider->getCountryList($includeInternalCodes = true);

        $metricsByLanguage = new DataTable();

        while ($row = $query->fetch()) {
            $langCode = Common::extractLanguageCodeFromBrowserLanguage($row['label']);
            $countryCode = Common::extractCountryCodeFromBrowserLanguage($row['label'], $countryCodes, $enableLanguageToCountryGuess = true);

            if ($countryCode == 'xx' || $countryCode == $langCode) {
                $label = $langCode;
            } else {
                $label = $langCode . '-' . $countryCode;
            }

            $columns = [
                Metrics::INDEX_NB_UNIQ_VISITORS => $row[Metrics::INDEX_NB_UNIQ_VISITORS],
                Metrics::INDEX_NB_VISITS => $row[Metrics::INDEX_NB_VISITS],
                Metrics::INDEX_NB_ACTIONS => $row[Metrics::INDEX_NB_ACTIONS],
                Metrics::INDEX_NB_USERS => $row[Metrics::INDEX_NB_USERS],
                Metrics::INDEX_MAX_ACTIONS => $row[Metrics::INDEX_MAX_ACTIONS],
                Metrics::INDEX_SUM_VISIT_LENGTH => $row[Metrics::INDEX_SUM_VISIT_LENGTH],
                Metrics::INDEX_BOUNCE_COUNT => $row[Metrics::INDEX_BOUNCE_COUNT],
                Metrics::INDEX_NB_VISITS_CONVERTED => $row[Metrics::INDEX_NB_VISITS_CONVERTED],
            ];

            $metricsByLanguage->sumRowWithLabel($label, $columns);
        }

        return [
            Archiver::LANGUAGE_RECORD_NAME => $metricsByLanguage,
        ];
    }
}
