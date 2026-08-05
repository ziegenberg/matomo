<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager;

use Matomo\Common;
use Matomo\Config;
use Matomo\DbHelper;
use Matomo\Exception\UnexpectedWebsiteFoundException;
use Matomo\Period\PeriodValidator;
use Matomo\Matomo;
use Matomo\Plugins\SitesManager\API as APISitesManager;
use Matomo\Plugins\UsersManager\API as APIUsersManager;
use Matomo\Site;

class UserPreferences
{
    /**
     * @var APIUsersManager
     */
    private $api;

    public function __construct()
    {
        $this->api = APIUsersManager::getInstance();
    }

    /**
     * Returns default site ID that Piwik should load.
     *
     * _Note: This value is a Piwik setting set by each user._
     *
     * @return bool|int
     * @api
     */
    public function getDefaultWebsiteId()
    {
        $defaultReport = $this->getDefaultReport();

        if (is_numeric($defaultReport) && Matomo::isUserHasViewAccess($defaultReport)) {
            return $defaultReport;
        }

        $sitesId = APISitesManager::getInstance()->getSitesIdWithAtLeastViewAccess();

        if (!empty($sitesId)) {
            return $sitesId[0];
        }

        return false;
    }

    /**
     * Returns user light/dark mode preference.
     *
     * @api
     */
    public function getThemeMode(): string
    {
        if (!DbHelper::tableExists(Common::prefixTable('plugin_setting'))) {
            return APIUsersManager::PREFERENCE_DEFAULT_THEME_MODE;
        }

        return $this->api->getUserPreference(
            APIUsersManager::PREFERENCE_THEME_MODE,
            Matomo::getCurrentUserLogin()
        );
    }

    /**
     * Returns default site ID that Piwik should load.
     *
     * _Note: This value is a Piwik setting set by each user._
     *
     * @return bool|int
     * @api
     */
    public function getDefaultReport()
    {
        // User preference: default website ID to load
        $defaultReport = $this->api->getUserPreference(
            APIUsersManager::PREFERENCE_DEFAULT_REPORT,
            Matomo::getCurrentUserLogin()
        );

        if (!is_numeric($defaultReport)) {
            return $defaultReport;
        }

        $defaultReport = (int) $defaultReport;

        if ($this->isValidWebsiteForCurrentUser($defaultReport)) {
            return $defaultReport;
        }

        return false;
    }

    private function isValidWebsiteForCurrentUser(int $idSite): bool
    {
        if (empty($idSite)) {
            return false;
        }

        if (!Matomo::isUserHasViewAccess($idSite)) {
            return false;
        }

        try {
            new Site($idSite);
        } catch (UnexpectedWebsiteFoundException $e) {
            return false;
        }

        return true;
    }
    /**
     * Returns default date for Piwik reports.
     *
     * _Note: This value is a Piwik setting set by each user._
     *
     * @return string `'today'`, `'2010-01-01'`, etc.
     * @api
     */
    public function getDefaultDate()
    {
        list($defaultDate, $defaultPeriod) = $this->getDefaultDateAndPeriod();

        return $defaultDate;
    }

    /**
     * Returns default period type for Piwik reports.
     *
     * @param string $defaultDate the default date string from which the default period will be guessed
     * @return string `'day'`, `'week'`, `'month'`, `'year'` or `'range'`
     * @api
     */
    public function getDefaultPeriod($defaultDate = null)
    {
        list($defaultDate, $defaultPeriod) = $this->getDefaultDateAndPeriod($defaultDate);

        return $defaultPeriod;
    }

    private function getDefaultDateAndPeriod($defaultDate = null)
    {
        $defaultPeriod = $this->getDefaultPeriodWithoutValidation($defaultDate);
        if (! $defaultDate) {
            $defaultDate = $this->getDefaultDateWithoutValidation();
        }

        $periodValidator = new PeriodValidator();
        if (! $periodValidator->isPeriodAllowedForUI($defaultPeriod)) {
            $defaultDate = $this->getSystemDefaultDate();
            $defaultPeriod = $this->getSystemDefaultPeriod();
        }

        return array($defaultDate, $defaultPeriod);
    }

    public function getDefaultDateWithoutValidation()
    {
        // NOTE: a change in this function might mean a change in plugins/UsersManager/javascripts/usersSettings.js as well
        $userSettingsDate = $this->api->getUserPreference(
            APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE,
            Matomo::getCurrentUserLogin()
        );
        if ($userSettingsDate == 'yesterday') {
            return $userSettingsDate;
        }
        // if last7, last30, etc.
        if (
            strpos($userSettingsDate, 'last') === 0
            || strpos($userSettingsDate, 'previous') === 0
        ) {
            return $userSettingsDate;
        }

        return 'today';
    }

    public function getDefaultPeriodWithoutValidation($defaultDate = null)
    {
        if (empty($defaultDate)) {
            $defaultDate = $this->api->getUserPreference(
                APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE,
                Matomo::getCurrentUserLogin()
            );
        }

        if (empty($defaultDate)) {
            return $this->getSystemDefaultPeriod();
        }

        if (in_array($defaultDate, array('today', 'yesterday'))) {
            return 'day';
        }

        if (
            strpos($defaultDate, 'last') === 0
            || strpos($defaultDate, 'previous') === 0
        ) {
            return 'range';
        }

        return $defaultDate;
    }

    private function getSystemDefaultDate()
    {
        return Config::getInstance()->General['default_day'];
    }

    private function getSystemDefaultPeriod()
    {
        return Config::getInstance()->General['default_period'];
    }
}
