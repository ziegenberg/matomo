<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UsersManager;

use Exception;
use Matomo\API\Request;
use Matomo\API\ResponseBuilder;
use Matomo\Auth\PasswordStrength;
use Matomo\Common;
use Matomo\Config\GeneralConfig;
use Matomo\Container\StaticContainer;
use Matomo\Date;
use Matomo\Nonce;
use Matomo\Notification;
use Matomo\Option;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Plugin\ControllerAdmin;
use Matomo\Plugin\ThemeStyles;
use Matomo\Plugins\LanguagesManager\API as APILanguagesManager;
use Matomo\Plugins\LanguagesManager\LanguagesManager;
use Matomo\Plugins\Login\PasswordVerifier;
use Matomo\Plugins\UsersManager\API as APIUsersManager;
use Matomo\Settings\Storage\UserScopedSettingsAccessManager;
use Matomo\SettingsPiwik;
use Matomo\Site;
use Matomo\Tracker\Cache;
use Matomo\Tracker\IgnoreCookie;
use Matomo\Translation\Translator;
use Matomo\Url;
use Matomo\View;
use Matomo\Session\SessionInitializer;
use Matomo\Plugins\CoreAdminHome\Emails\TokenAuthCreatedEmail;
use Matomo\Plugins\CoreAdminHome\Emails\TokenAuthDeletedEmail;

class Controller extends ControllerAdmin
{
    public const NONCE_CHANGE_PASSWORD = 'changePasswordNonce';
    public const NONCE_ADD_AUTH_TOKEN = 'addAuthTokenNonce';
    public const NONCE_DELETE_AUTH_TOKEN = 'deleteAuthTokenNonce';
    public const NONCE_SET_IGNORE_COOKIE = 'setIgnoreCookieNonce';

    private Translator $translator;

    private PasswordVerifier $passwordVerify;

    /**
     * @var Plugin\Manager
     */
    private $pluginManager;

    private Model $userModel;

    private PasswordStrength $passwordStrength;

    public function __construct(
        Translator $translator,
        PasswordVerifier $passwordVerify,
        Model $userModel,
        PasswordStrength $passwordStrength
    ) {
        $this->translator = $translator;
        $this->passwordVerify = $passwordVerify;
        $this->userModel = $userModel;
        $this->passwordStrength = $passwordStrength;
        $this->pluginManager = Plugin\Manager::getInstance();

        parent::__construct();
    }

    /**
     * The "Manage Users and Permissions" Admin UI screen
     */
    public function index()
    {
        Matomo::checkUserIsNotAnonymous();
        Matomo::checkUserHasSomeAdminAccess();
        UsersManager::dieIfUsersAdminIsDisabled();

        $view = new View('@UsersManager/index');

        $IdSitesAdmin = Request::processRequest('SitesManager.getSitesIdWithAdminAccess');
        $idSiteSelected = 1;

        if (count($IdSitesAdmin) > 0) {
            $defaultWebsiteId = $IdSitesAdmin[0];
            $idSiteSelected = $this->idSite ?: $defaultWebsiteId;
        }

        if (!Matomo::isUserHasAdminAccess($idSiteSelected) && count($IdSitesAdmin) > 0) {
            // make sure to show a website where user actually has admin access
            $idSiteSelected = $IdSitesAdmin[0];
        }

        $defaultReportSiteName = Site::getNameFor($idSiteSelected);

        $view->inviteTokenExpiryDays = GeneralConfig::getConfigValue('default_invite_user_token_expiry_days');
        $view->idSiteSelected = $idSiteSelected;
        $view->defaultReportSiteName = $defaultReportSiteName;
        $view->currentUserRole = Matomo::hasUserSuperUserAccess() ? 'superuser' : 'admin';
        $view->accessLevels = [
            ['key' => 'noaccess', 'value' => Matomo::translate('UsersManager_PrivNone'), 'type' => 'role'],
            ['key' => 'view', 'value' => Matomo::translate('UsersManager_PrivView'), 'type' => 'role'],
            ['key' => 'write', 'value' => Matomo::translate('UsersManager_PrivWrite'), 'type' => 'role'],
            ['key' => 'admin', 'value' => Matomo::translate('UsersManager_PrivAdmin'), 'type' => 'role'],
            ['key' => 'superuser', 'value' => Matomo::translate('Installation_SuperUser'), 'type' => 'role', 'disabled' => true],
        ];
        $view->filterAccessLevels = [
            ['key' => '', 'value' => '', 'type' => 'role'], // show all
            ['key' => 'noaccess', 'value' => Matomo::translate('UsersManager_PrivNone'), 'type' => 'role'],
            ['key' => 'some', 'value' => Matomo::translate('UsersManager_AtLeastView'), 'type' => 'role'],
            ['key' => 'view', 'value' => Matomo::translate('UsersManager_PrivView'), 'type' => 'role'],
            ['key' => 'write', 'value' => Matomo::translate('UsersManager_PrivWrite'), 'type' => 'role'],
            ['key' => 'admin', 'value' => Matomo::translate('UsersManager_PrivAdmin'), 'type' => 'role'],
            ['key' => 'superuser', 'value' => Matomo::translate('Installation_SuperUser'), 'type' => 'role'],
        ];

        $view->statusAccessLevels = [
          ['key' => '', 'value' => ''], // show all
          ['key' => 'pending', 'value' => Matomo::translate('UsersManager_Pending')],
          ['key' => 'active', 'value' => Matomo::translate('UsersManager_Active')],
          ['key' => 'expired', 'value' => Matomo::translate('UsersManager_Expired')],
        ];

        $capabilities = Request::processRequest('UsersManager.getAvailableCapabilities', [], []);
        foreach ($capabilities as $capability) {
            $capabilityEntry = [
                'key' => $capability['id'],
                'value' => $capability['category'] . ': ' . $capability['name'],
                'type' => 'capability',
            ];
            $view->accessLevels[] = $capabilityEntry;
            $view->filterAccessLevels[] = $capabilityEntry;
        }

        $view->activatedPlugins = $this->pluginManager->getActivatedPlugins();

        /** @var array{'inviteComponent': string, 'resendInviteComponent': string } $inviteVueComponents */
        $inviteVueComponents = [
            'inviteComponent' => 'UsersManager.UserInvite',
            'resendInviteComponent' => 'UsersManager.ResendInviteModal',
        ];
        Matomo::postEvent('UsersManager.getInviteVueComponents', [&$inviteVueComponents]);

        $view->inviteComponent = $inviteVueComponents['inviteComponent'];
        $view->resendInviteComponent = $inviteVueComponents['resendInviteComponent'];

        $view->passwordStrengthValidationRules = $this->passwordStrength->getRules();

        $this->setBasicVariablesView($view);

        return $view->render();
    }

    /**
     * Returns default date for Piwik reports
     *
     * @param string $user
     * @return string today, yesterday, week, month, year
     */
    protected function getDefaultDateForUser($user)
    {
        return APIUsersManager::getInstance()->getUserPreference(APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE, $user);
    }

    /**
     * Returns the enabled dates that users can select,
     * in their User Settings page "Report date to load by default"
     *
     * @return array
     */
    protected function getDefaultDates()
    {
        $dates = array(
            'today'      => $this->translator->translate('Intl_Today'),
            'yesterday'  => $this->translator->translate('Intl_Yesterday'),
            'previous7'  => $this->translator->translate('General_PreviousDays', 7),
            'previous30' => $this->translator->translate('General_PreviousDays', 30),
            'last7'      => $this->translator->translate('General_LastDays', 7),
            'last30'     => $this->translator->translate('General_LastDays', 30),
            'week'       => $this->translator->translate('General_CurrentWeek'),
            'month'      => $this->translator->translate('General_CurrentMonth'),
            'year'       => $this->translator->translate('General_CurrentYear'),
        );

        $mappingDatesToPeriods = array(
            'today' => 'day',
            'yesterday' => 'day',
            'previous7' => 'range',
            'previous30' => 'range',
            'last7' => 'range',
            'last30' => 'range',
            'week' => 'week',
            'month' => 'month',
            'year' => 'year',
        );

        // assertion
        if (count($dates) != count($mappingDatesToPeriods)) {
            throw new Exception("some metadata is missing in getDefaultDates()");
        }

        $allowedPeriods = self::getEnabledPeriodsInUI();
        $allowedDates = array_intersect($mappingDatesToPeriods, $allowedPeriods);
        $dates = array_intersect_key($dates, $allowedDates);

        /**
         * Triggered when the list of available dates is requested, for example for the
         * User Settings > Report date to load by default.
         *
         * @param array &$dates Array of (date => translation)
         */
        Matomo::postEvent('UsersManager.getDefaultDates', array(&$dates));

        return $dates;
    }

    /**
     * The "User Settings" admin UI screen view
     */
    public function userSettings()
    {
        Matomo::checkUserIsNotAnonymous();

        $view = new View('@UsersManager/userSettings');

        $userLogin = Matomo::getCurrentUserLogin();
        $user = Request::processRequest('UsersManager.getUser', array('userLogin' => $userLogin));
        $view->userEmail = $user['email'] ?? '';
        $view->userTokenAuth = Matomo::getCurrentUserTokenAuth();
        $view->setIgnoreCookieNonce = Nonce::getNonce(self::NONCE_SET_IGNORE_COOKIE);
        $view->isUsersAdminEnabled = UsersManager::isUsersAdminEnabled();

        $newsletterSignupOptionKey = NewsletterSignup::NEWSLETTER_SIGNUP_OPTION . $userLogin;
        $view->showNewsletterSignup = Option::get($newsletterSignupOptionKey) === false
                                    && SettingsPiwik::isInternetEnabled();

        $userPreferences = new UserPreferences();

        $view->themeMode = $userPreferences->getThemeMode();
        $view->themeModeOptions = array(
            array('key' => ThemeStyles::LIGHT_MODE, 'value' => Matomo::translate('UsersManager_ThemeModeLightDefault')),
            array('key' => ThemeStyles::DARK_MODE, 'value' => Matomo::translate('UsersManager_ThemeModeDark')),
            array('key' => ThemeStyles::AUTO_MODE, 'value' => Matomo::translate('UsersManager_ThemeModeMatchBrowser')),
        );

        $storedDefaultReport = $this->getStoredDefaultReportForUser($userLogin);
        $defaultReport   = $userPreferences->getDefaultReport();

        if (is_numeric($storedDefaultReport) && $defaultReport === false) {
            $defaultReport = $userPreferences->getDefaultWebsiteId();
            $this->persistDefaultReportForUser($userLogin, $defaultReport);
        }

        if ($defaultReport === false) {
            $defaultReport = $userPreferences->getDefaultWebsiteId();
        }

        $view->defaultReport = $defaultReport;

        if ($defaultReport == 'MultiSites') {
            $defaultSiteId = $userPreferences->getDefaultWebsiteId();
            $reportOptionsValue = $defaultSiteId;

            $view->defaultReportIdSite   = $defaultSiteId;
            $view->defaultReportSiteName = Site::getNameFor($defaultSiteId);
        } else {
            $reportOptionsValue = $defaultReport;
            $view->defaultReportIdSite   = $defaultReport;
            $view->defaultReportSiteName = Site::getNameFor($defaultReport);
        }

        $defaultReportOptions = array();
        if (Plugin\Manager::getInstance()->isPluginActivated('MultiSites')) {
            $defaultReportOptions[] = array('key' => 'MultiSites', 'value' => Matomo::translate('General_AllWebsitesDashboard'));
        }

        $defaultReportOptions[] = array('key' => $reportOptionsValue, 'value' => Matomo::translate('General_DashboardForASpecificWebsite'));

        $view->defaultReportOptions = $defaultReportOptions;
        $view->defaultDate = $this->getDefaultDateForUser($userLogin);
        $view->availableDefaultDates = $this->getDefaultDates();

        $languages = APILanguagesManager::getInstance()->getAvailableLanguageNames();
        $languageOptions = array();
        foreach ($languages as $language) {
            $languageOptions[] = array(
                'key' => $language['code'],
                'value' => $language['name'],
            );
        }

        $view->languageOptions = $languageOptions;
        $view->currentLanguageCode = LanguagesManager::getLanguageCodeForCurrentUser();
        $view->currentTimeformat = (int) LanguagesManager::uses12HourClockForCurrentUser();
        $view->ignoreCookieSet = IgnoreCookie::isIgnoreCookieFound();
        $view->piwikHost = Url::getCurrentHost();
        $this->setBasicVariablesView($view);

        $view->timeFormats = array(
            '1' => Matomo::translate('General_12HourClock'),
            '0' => Matomo::translate('General_24HourClock'),
        );

        return $view->render();
    }

    /**
     * @return false|int|string
     */
    private function getStoredDefaultReportForUser(string $userLogin)
    {
        return StaticContainer::get(UserScopedSettingsAccessManager::class)->get(
            'UsersManager',
            $userLogin,
            APIUsersManager::PREFERENCE_DEFAULT_REPORT,
            false
        );
    }

    /**
     * @param false|int $defaultReport
     */
    private function persistDefaultReportForUser(string $userLogin, $defaultReport): void
    {
        $store = StaticContainer::get(UserScopedSettingsAccessManager::class);

        if ($defaultReport === false) {
            $store->delete('UsersManager', $userLogin, APIUsersManager::PREFERENCE_DEFAULT_REPORT);
            return;
        }

        $store->set('UsersManager', $userLogin, APIUsersManager::PREFERENCE_DEFAULT_REPORT, $defaultReport);
    }

    /**
     * The "User Security" admin UI screen view
     *
     * @return array|null|string
     */
    public function userSecurity()
    {
        Matomo::checkUserIsNotAnonymous();

        $tokens = $this->userModel->getAllNonSystemTokensForLogin(Matomo::getCurrentUserLogin());
        $tokens = array_map(function ($token) {
            foreach (['date_created', 'last_used', 'date_expired'] as $key) {
                if (!empty($token[$key])) {
                    $token[$key] = Date::factory($token[$key])->getLocalized(Date::DATE_FORMAT_LONG);
                }
            }
            unset($token['password']);
            return $token;
        }, $tokens);

        return $this->renderTemplate('userSecurity', [
            'isUsersAdminEnabled' => UsersManager::isUsersAdminEnabled(),
            'changePasswordNonce' => Nonce::getNonce(self::NONCE_CHANGE_PASSWORD),
            'deleteTokenNonce' => Nonce::getNonce(self::NONCE_DELETE_AUTH_TOKEN),
            'tokens' => $tokens,
            'passwordStrengthValidationRules' => $this->passwordStrength->getRules(),
        ]);
    }

    /**
     * The "User Security" admin UI screen view
     */
    public function deleteToken()
    {
        Matomo::checkUserIsNotAnonymous();

        $idTokenAuth = Common::getRequestVar('idtokenauth', '', 'string');

        if (!empty($idTokenAuth)) {
            $params = array(
                'module' => 'UsersManager',
                'action' => 'deleteToken',
                'idtokenauth' => $idTokenAuth,
                'nonce' => Nonce::getNonce(self::NONCE_DELETE_AUTH_TOKEN),
            );

            if (!$this->passwordVerify->requirePasswordVerifiedRecently($params)) {
                throw new Exception('Not allowed');
            }

            Nonce::checkNonce(self::NONCE_DELETE_AUTH_TOKEN);

            if ($idTokenAuth === 'all') {
                $this->userModel->deleteAllTokensForUser(Matomo::getCurrentUserLogin());

                $notification = new Notification(Matomo::translate('UsersManager_TokensSuccessfullyDeleted'));
                $notification->context = Notification::CONTEXT_SUCCESS;
                Notification\Manager::notify('successdeletetokens', $notification);

                $container = StaticContainer::getContainer();
                $email = $container->make(TokenAuthDeletedEmail::class, array(
                    'login' => Matomo::getCurrentUserLogin(),
                    'emailAddress' => Matomo::getCurrentUserEmail(),
                    'tokenDescription' => '',
                    'all' => true,
                ));
                $email->safeSend();
            } elseif (is_numeric($idTokenAuth)) {
                $description = $this->userModel->getUserTokenDescriptionByIdTokenAuth($idTokenAuth, Matomo::getCurrentUserLogin());
                $this->userModel->deleteToken($idTokenAuth, Matomo::getCurrentUserLogin());

                $notification = new Notification(Matomo::translate('UsersManager_TokenSuccessfullyDeleted'));
                $notification->context = Notification::CONTEXT_SUCCESS;
                Notification\Manager::notify('successdeletetoken', $notification);

                $container = StaticContainer::getContainer();
                $email = $container->make(TokenAuthDeletedEmail::class, array(
                    'login' => Matomo::getCurrentUserLogin(),
                    'emailAddress' => Matomo::getCurrentUserEmail(),
                    'tokenDescription' => $description,
                ));
                $email->safeSend();
            }

            Cache::deleteTrackerCache();
        }

        $this->redirectToIndex('UsersManager', 'userSecurity');
    }

    /**
     * The "User Security" admin UI screen view
     */
    public function addNewToken()
    {
        Matomo::checkUserIsNotAnonymous();

        $params = ['module' => 'UsersManager', 'action' => 'addNewToken'];

        if (!$this->passwordVerify->requirePasswordVerifiedRecently($params)) {
            throw new Exception('Not allowed');
        }

        $postRequest = \Matomo\Request::fromPost();
        $postRequestHasData = count($postRequest->getParameters());

        $today = Date::factory('now');

        $tokenExpireDate = $postRequest->getStringParameter('token_expire_date', '');
        $invalidExpireDate = true;
        try {
            if ($tokenExpireDate && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tokenExpireDate)) {
                $expireDate = Date::factory($tokenExpireDate);
                if ($expireDate->isLater($today)) {
                    $invalidExpireDate = false;
                }
            }
        } catch (Exception $e) {
            // nop
        }

        $description = $postRequest->getStringParameter('description', '');
        $noDescription = empty($description);

        if (false === $noDescription && false === $invalidExpireDate) {
            Nonce::checkNonce(self::NONCE_ADD_AUTH_TOKEN);
            $secureOnly = $postRequest->getBoolParameter('secure_only', false);
            $hasTokenExpiry = $postRequest->getBoolParameter('has_expiration', false);

            $login = Matomo::getCurrentUserLogin();

            $generatedToken = $this->userModel->generateRandomTokenAuth();

            $this->userModel->addTokenAuth(
                $login,
                $generatedToken,
                $description,
                $today->getDatetime(),
                $hasTokenExpiry ? $tokenExpireDate : null,
                false,
                $secureOnly
            );

            $container = StaticContainer::getContainer();
            $email = $container->make(TokenAuthCreatedEmail::class, [
                'login' => Matomo::getCurrentUserLogin(),
                'emailAddress' => Matomo::getCurrentUserEmail(),
                'tokenDescription' => $description,
            ]);
            $email->safeSend();

            return $this->renderTemplate('addNewTokenSuccess', ['generatedToken' => $generatedToken]);
        }

        $defaultExpireDays = GeneralConfig::getConfigValue('auth_token_default_expiration_days');

        return $this->renderTemplate('addNewToken', [
            'nonce' => Nonce::getNonce(self::NONCE_ADD_AUTH_TOKEN),
            'noDescription' => $postRequestHasData && $noDescription,
            'invalidExpireDate' => $postRequestHasData && $invalidExpireDate,
            'forceSecureOnly' => GeneralConfig::getBoolConfigValue('only_allow_secure_auth_tokens', false),
            'initialExpireDate' => $today->addDay($defaultExpireDays)->toString(),
            'defaultExpirationDays' => $defaultExpireDays,
            'expirationReminderDays' => GeneralConfig::getConfigValue('auth_token_expiration_notification_days'),
        ]);
    }

    /**
     * The "Anonymous Settings" admin UI screen view
     */
    public function anonymousSettings()
    {
        Matomo::checkUserHasSuperUserAccess();

        $view = new View('@UsersManager/anonymousSettings');

        $view->availableDefaultDates = $this->getDefaultDates();

        $this->initViewAnonymousUserSettings($view);
        $this->setBasicVariablesView($view);

        return $view->render();
    }

    public function setIgnoreCookie()
    {
        Matomo::checkUserHasSomeViewAccess();
        Matomo::checkUserIsNotAnonymous();

        Nonce::checkNonce(self::NONCE_SET_IGNORE_COOKIE);

        IgnoreCookie::setIgnoreCookie();
        Matomo::redirectToModule('UsersManager', 'userSettings', ['nonce' => false]);
    }

    /**
     * The Super User can modify Anonymous user settings
     * @param View $view
     */
    protected function initViewAnonymousUserSettings($view)
    {
        if (!Matomo::hasUserSuperUserAccess()) {
            return;
        }

        $userLogin = 'anonymous';

        // Which websites are available to the anonymous users?

        $anonymousSitesAccess = Request::processRequest('UsersManager.getSitesAccessFromUser', array('userLogin' => $userLogin));
        $anonymousSites = array();
        $idSites = array();
        foreach ($anonymousSitesAccess as $info) {
            $idSite = $info['site'];
            $idSites[] = $idSite;

            $site = Request::processRequest('SitesManager.getSiteFromId', array('idSite' => $idSite));
            // Work around manual website deletion
            if (!empty($site)) {
                $anonymousSites[] = array('key' => $idSite, 'value' => Common::unsanitizeInputValue($site['name']));
            }
        }
        $view->anonymousSites = $anonymousSites;

        $anonymousDefaultSite = '';

        // Which report is displayed by default to the anonymous user?
        $anonymousDefaultReport = Request::processRequest('UsersManager.getUserPreference', array('userLogin' => $userLogin, 'preferenceName' => APIUsersManager::PREFERENCE_DEFAULT_REPORT));
        if ($anonymousDefaultReport === false) {
            if (empty($anonymousSites)) {
                $anonymousDefaultReport = Matomo::getLoginPluginName();
            } else {
                // we manually imitate what would happen, in case the anonymous user logs in
                // and is redirected to the first website available to them in the list
                // @see getDefaultWebsiteId()
                $anonymousDefaultReport = '1';
                $anonymousDefaultSite = $anonymousSites[0]['key'];
            }
        }

        if (is_numeric($anonymousDefaultReport)) {
            $anonymousDefaultSite = $anonymousDefaultReport;
            $anonymousDefaultReport = '1'; // a website is selected, we make sure "Dashboard for a specific site" gets pre-selected
        }

        if ((empty($anonymousDefaultSite) || !in_array($anonymousDefaultSite, $idSites)) && !empty($idSites)) {
            $anonymousDefaultSite = $anonymousSites[0]['key'];
        }

        $view->anonymousDefaultReport = $anonymousDefaultReport;
        $view->anonymousDefaultSite = $anonymousDefaultSite;
        $view->anonymousDefaultDate = $this->getDefaultDateForUser($userLogin);

        $view->defaultReportOptions = array(
            array('key' => 'Login', 'value' => Matomo::translate('UsersManager_TheLoginScreen')),
            array('key' => 'MultiSites', 'value' => Matomo::translate('General_AllWebsitesDashboard'), 'disabled' => empty($anonymousSites)),
            array('key' => '1', 'value' => Matomo::translate('General_DashboardForASpecificWebsite')),
        );
    }

    /**
     * Records settings for the anonymous users (default report, default date)
     */
    public function recordAnonymousUserSettings()
    {
        $response = new ResponseBuilder(Common::getRequestVar('format'));
        try {
            Matomo::checkUserHasSuperUserAccess();
            $this->checkTokenInUrl();

            $anonymousDefaultReport = Common::getRequestVar('anonymousDefaultReport');
            $anonymousDefaultDate = Common::getRequestVar('anonymousDefaultDate');
            $userLogin = 'anonymous';
            APIUsersManager::getInstance()->setUserPreference(
                $userLogin,
                APIUsersManager::PREFERENCE_DEFAULT_REPORT,
                $anonymousDefaultReport
            );
            APIUsersManager::getInstance()->setUserPreference(
                $userLogin,
                APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE,
                $anonymousDefaultDate
            );
            $toReturn = $response->getResponse();
        } catch (Exception $e) {
            $toReturn = $response->getResponseException($e);
        }

        return $toReturn;
    }

    /**
     * Records settings from the "User Settings" page
     * @throws Exception
     */
    public function recordUserSettings()
    {
        $response = new ResponseBuilder(Common::getRequestVar('format'));
        try {
            $this->checkTokenInUrl();

            $themeMode = $this->getValidatedThemeMode(Common::getRequestVar('themeMode'));
            $defaultReport = Common::getRequestVar('defaultReport');
            $defaultDate = Common::getRequestVar('defaultDate');
            $language = Common::getRequestVar('language');
            $timeFormat = Common::getRequestVar('timeformat');
            $userLogin = Matomo::getCurrentUserLogin();

            Matomo::checkUserHasSuperUserAccessOrIsTheUser($userLogin);

            $this->processEmailChange($userLogin);

            LanguagesManager::setLanguageForSession($language);

            Request::processRequest('LanguagesManager.setLanguageForUser', [
                'login' => $userLogin,
                'languageCode' => $language,
            ]);
            Request::processRequest('LanguagesManager.set12HourClockForUser', [
                'login' => $userLogin,
                'use12HourClock' => $timeFormat,
            ]);

            $currentThemeMode = (new UserPreferences())->getThemeMode();
            if ($currentThemeMode !== $themeMode) {
                APIUsersManager::getInstance()->setUserPreference(
                    $userLogin,
                    APIUsersManager::PREFERENCE_THEME_MODE,
                    $themeMode
                );
            }

            APIUsersManager::getInstance()->setUserPreference(
                $userLogin,
                APIUsersManager::PREFERENCE_DEFAULT_REPORT,
                $defaultReport
            );
            APIUsersManager::getInstance()->setUserPreference(
                $userLogin,
                APIUsersManager::PREFERENCE_DEFAULT_REPORT_DATE,
                $defaultDate
            );
            $toReturn = $response->getResponse();
        } catch (Exception $e) {
            $toReturn = $response->getResponseException($e);
        }

        return $toReturn;
    }

    private function getValidatedThemeMode(string $themeMode): string
    {
        $allowedThemeModes = [
            ThemeStyles::AUTO_MODE,
            ThemeStyles::LIGHT_MODE,
            ThemeStyles::DARK_MODE,
        ];

        if (!in_array($themeMode, $allowedThemeModes, true)) {
            throw new Exception('Invalid theme mode');
        }

        return $themeMode;
    }


    /**
     * Records settings from the "User Settings" page
     * @throws Exception
     */
    public function recordPasswordChange()
    {
        $userLogin = Matomo::getCurrentUserLogin();

        Matomo::checkUserHasSuperUserAccessOrIsTheUser($userLogin);
        Nonce::checkNonce(self::NONCE_CHANGE_PASSWORD);

        $this->processPasswordChange($userLogin);

        $notification = new Notification(Matomo::translate('CoreAdminHome_SettingsSaveSuccess'));
        $notification->context = Notification::CONTEXT_SUCCESS;
        Notification\Manager::notify('successpass', $notification);
        $this->redirectToIndex('UsersManager', 'userSecurity');
    }

    private function processEmailChange($userLogin)
    {
        if (!UsersManager::isUsersAdminEnabled()) {
            return;
        }

        if (!Url::isValidHost()) {
            throw new Exception("Cannot change email with untrusted hostname!");
        }

        $request = \Matomo\Request::fromRequest();
        $email = $request->getStringParameter('email');
        $passwordCurrent = $request->getStringParameter('passwordConfirmation', '');

        // UI disables password change on invalid host, but check here anyway
        Request::processRequest('UsersManager.updateUser', [
            'userLogin' => $userLogin,
            'email' => $email,
            'passwordConfirmation' => $passwordCurrent,
        ], $default = []);
    }

    private function processPasswordChange($userLogin)
    {
        if (!UsersManager::isUsersAdminEnabled()) {
            return;
        }

        if (!Url::isValidHost()) {
            // UI disables password change on invalid host, but check here anyway
            throw new Exception("Cannot change password with untrusted hostname!");
        }

        $request = \Matomo\Request::fromRequest();
        $newPassword = $request->getStringParameter('password', '');
        $passwordBis = $request->getStringParameter('passwordBis', '');
        $passwordCurrent = $request->getStringParameter('passwordConfirmation', '');

        if ($newPassword !== $passwordBis) {
            throw new Exception($this->translator->translate('Login_PasswordsDoNotMatch'));
        }

        if ($newPassword === $passwordCurrent) {
            throw new Exception($this->translator->translate('UsersManager_PasswordAlreadyInUse'));
        }

        // check password is sufficiently strong
        $brokenRules = $this->passwordStrength->validatePasswordStrength($newPassword);
        if (!empty($brokenRules)) {
            $errorMsg = $this->passwordStrength->formatValidationFailedMessage($brokenRules);
            throw new Exception($errorMsg);
        }

        Request::processRequest('UsersManager.updateUser', [
            'userLogin' => $userLogin,
            'password' => $newPassword,
            'passwordConfirmation' => $passwordCurrent,
        ], $default = []);

        // logs the user in with the new password
        $newPassword = Common::unsanitizeInputValue($newPassword);
        $sessionInitializer = new SessionInitializer();
        $auth = StaticContainer::get('Matomo\Auth');
        $auth->setTokenAuth(null); // ensure authenticated through password
        $auth->setLogin($userLogin);
        $auth->setPassword($newPassword);
        $sessionInitializer->initSession($auth);
    }
}
