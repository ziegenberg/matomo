<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\PrivacyManager;

use Matomo\Plugins\PrivacyManager\Model\DataSubjects;
use Matomo\Plugins\PrivacyManager\Model\LogDataAnonymizations;
use Matomo\Plugins\SitesManager\API as SitesManagerAPI;

class Tasks extends \Matomo\Plugin\Tasks
{
    private LogDataAnonymizations $logDataAnonymizations;

    private DataSubjects $dataSubjects;

    private SitesManagerAPI $sitesManagerAPI;

    public function __construct(LogDataAnonymizations $logDataAnonymizations, DataSubjects $dataSubjects, SitesManagerAPI $sitesManagerAPI)
    {
        $this->logDataAnonymizations = $logDataAnonymizations;
        $this->dataSubjects = $dataSubjects;
        $this->sitesManagerAPI = $sitesManagerAPI;
    }

    public function schedule()
    {
        $this->daily('deleteReportData', null, self::LOW_PRIORITY);
        $this->hourly('deleteLogData', null, self::LOW_PRIORITY);
        $this->hourly('anonymizePastData', null, self::LOW_PRIORITY);
        $this->weekly('deleteLogDataForDeletedSites', null, self::LOW_PRIORITY);
    }

    public function anonymizePastData()
    {
        $loop = 0;
        do {
            $loop++; // safety loop...
            $id = $this->logDataAnonymizations->getNextScheduledAnonymizationId();
            if (!empty($id)) {
                $this->logDataAnonymizations->executeScheduledEntry($id);
            }
        } while (!empty($id) && $loop < 100);
    }

    public function deleteReportData()
    {
        $privacyManager = new PrivacyManager();
        $privacyManager->deleteReportData();
    }

    /**
     * To test execute the following command:
     * `./console core:run-scheduled-tasks "Matomo\Plugins\PrivacyManager\Tasks.deleteLogData"`
     */
    public function deleteLogData()
    {
        $privacyManager = new PrivacyManager();
        $privacyManager->deleteLogData();
    }

    public function deleteLogDataForDeletedSites()
    {
        $allSiteIds = $this->sitesManagerAPI->getAllSitesId();
        $this->dataSubjects->deleteDataSubjectsForDeletedSites($allSiteIds);
    }
}
