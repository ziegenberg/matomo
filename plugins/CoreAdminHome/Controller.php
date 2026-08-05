<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreAdminHome;

use Exception;
use Matomo\API\ResponseBuilder;
use Matomo\ArchiveProcessor\Rules;
use Matomo\Common;
use Matomo\Config;
use Matomo\Http\JsonResponse;
use Matomo\Mail;
use Matomo\Menu\MenuTop;
use Matomo\Matomo;
use Matomo\Plugin;
use Matomo\Plugin\ControllerAdmin;
use Matomo\Changes\UserChanges;
use Matomo\Plugins\CorePluginsAdmin\CorePluginsAdmin;
use Matomo\Plugins\Marketplace\Marketplace;
use Matomo\Plugins\CustomVariables\CustomVariables;
use Matomo\Plugins\LanguagesManager\LanguagesManager;
use Matomo\Plugins\Login\PasswordVerifier;
use Matomo\Plugins\PrivacyManager\DoNotTrackHeaderChecker;
use Matomo\Plugins\SitesManager\API as APISitesManager;
use Matomo\Request;
use Matomo\Site;
use Matomo\Translation\Translator;
use Matomo\Url;
use Matomo\UrlHelper;
use Matomo\View;
use Matomo\Widget\WidgetsList;
use Matomo\SettingsPiwik;
use Matomo\Plugins\UsersManager\Model as UsersModel;
use Matomo\Plugins\UsersManager\UserPreferences;

class Controller extends ControllerAdmin
{
    private Translator $translator;

    private OptOutManager $optOutManager;

    private PasswordVerifier $passwordVerify;

    public function __construct(Translator $translator, OptOutManager $optOutManager, PasswordVerifier $passwordVerify)
    {
        $this->translator = $translator;
        $this->optOutManager = $optOutManager;
        $this->passwordVerify = $passwordVerify;

        parent::__construct();
    }

    public function home()
    {
        $isInternetEnabled = SettingsPiwik::isInternetEnabled();

        $isMarketplaceEnabled = Marketplace::isMarketplaceEnabled();
        $isFeedbackEnabled = Plugin\Manager::getInstance()->isPluginLoaded('Feedback');
        $widgetsList = WidgetsList::get();

        if ($isInternetEnabled && $isMarketplaceEnabled) {
            $this->securityPolicy->addPolicy('img-src', '*.matomo.org');
        }

        $hasDonateForm = $widgetsList->isDefined('CoreHome', 'getDonateForm');
        $hasPiwikBlog = $widgetsList->isDefined('RssWidget', 'rssPiwik');
        $hasPremiumFeatures = $widgetsList->isDefined('Marketplace', 'getPremiumFeatures');
        $hasNewPlugins = $widgetsList->isDefined('Marketplace', 'getNewPlugins');
        $hasDiagnostics = $widgetsList->isDefined('Installation', 'getSystemCheck');
        $hasTrackingFailures = $widgetsList->isDefined('CoreAdminHome', 'getTrackingFailures');
        $hasQuickLinks = $widgetsList->isDefined('CoreHome', 'quickLinks');
        $hasSystemSummary = $widgetsList->isDefined('CoreHome', 'getSystemSummary');

        return $this->renderTemplate('home', array(
            'isInternetEnabled' => $isInternetEnabled,
            'isMarketplaceEnabled' => $isMarketplaceEnabled,
            'hasPremiumFeatures' => $hasPremiumFeatures,
            'hasNewPlugins' => $hasNewPlugins,
            'isFeedbackEnabled' => $isFeedbackEnabled,
            'hasDonateForm' => $hasDonateForm,
            'hasPiwikBlog' => $hasPiwikBlog,
            'hasDiagnostics' => $hasDiagnostics,
            'hasTrackingFailures' => $hasTrackingFailures,
            'hasQuickLinks' => $hasQuickLinks,
            'hasSystemSummary' => $hasSystemSummary,
        ));
    }

    public function index()
    {
        $this->redirectToIndex('UsersManager', 'userSettings');
        return;
    }

    public function trackingFailures()
    {
        Matomo::checkUserHasSomeAdminAccess();

        return $this->renderTemplate('trackingFailures');
    }

    public function generalSettings()
    {
        Matomo::checkUserHasSuperUserAccess();

        $view = new View('@CoreAdminHome/generalSettings');
        $this->handleGeneralSettingsAdmin($view);

        $view->trustedHosts = array_values(Url::getTrustedHostsFromConfig());
        $logo = new CustomLogo();
        $view->branding              = array('use_custom_logo' => $logo->isEnabled());
        $view->fileUploadEnabled     = $logo->isFileUploadEnabled();
        $view->logosWriteable        = $logo->isCustomLogoWritable();
        $view->customLogoEnabled     = $logo->isCustomLogoFeatureEnabled();
        $view->hasUserLogo           = CustomLogo::hasUserLogo();
        $view->pathUserLogo          = CustomLogo::getPathUserLogo();
        $view->hasUserFavicon        = CustomLogo::hasUserFavicon();
        $view->pathUserFavicon       = CustomLogo::getPathUserFavicon();
        $view->pathUserLogoSmall     = CustomLogo::getPathUserLogoSmall();
        $view->pathUserLogoSVG       = CustomLogo::getPathUserSvgLogo();
        $view->pathUserLogoDirectory = realpath(dirname($view->pathUserLogo) . '/');
        $view->mailTypes = array(
            '' => '',
            'Plain' => 'Plain',
            'Login' => 'Login',
            'Cram-md5' => 'Cram-md5',
        );
        $view->mailEncryptions = array(
            '' => 'auto',
            'ssl' => 'SSL',
            'tls' => 'TLS',
            'none' => 'none',
        );
        $mail = new Mail();
        $view->mailHost = $mail->getMailHost();

        $view->language = LanguagesManager::getLanguageCodeForCurrentUser();
        $this->setBasicVariablesView($view);
        return $view->render();
    }

    public function setMailSettings()
    {
        Matomo::checkUserHasSuperUserAccess();

        if (!self::isGeneralSettingsAdminEnabled()) {
            // General settings + Beta channel + SMTP settings is disabled
            return '';
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            throw new Exception('Invalid HTTP method.');
        }

        $response = new ResponseBuilder('json');
        try {
            $this->checkTokenInUrl();

            $request = Request::fromPost();

            // require password re-authentication before applying any changes
            $login = Matomo::getCurrentUserLogin();
            if (Matomo::doesUserRequirePasswordConfirmation($login)) {
                $passwordConfirmation = $request->getStringParameter('passwordConfirmation', '');
                if (
                    $passwordConfirmation === ''
                    || !$this->passwordVerify->isPasswordCorrect($login, $passwordConfirmation)
                ) {
                    throw new Exception(Matomo::translate('UsersManager_CurrentPasswordNotCorrect'));
                }
            }

            // Update email settings
            $mail = [];
            $mail['transport'] = $request->getBoolParameter('mailUseSmtp') ? 'smtp' : '';
            $mail['port'] = $request->getStringParameter('mailPort', '');
            $mail['host'] = $request->getStringParameter('mailHost', '');
            $mail['type'] = $request->getStringParameter('mailType', '');
            $mail['username'] = $request->getStringParameter('mailUsername', '');
            $mail['password'] = $request->getStringParameter('mailPassword', '');

            if (!array_key_exists('mailPassword', $request->getParameters()) && Config::getInstance()->mail['host'] === $mail['host']) {
                // use old password if it wasn't set in request (and the host wasn't changed)
                $mail['password'] = Config::getInstance()->mail['password'];
            }

            $mail['encryption'] =  $request->getStringParameter('mailEncryption', '');

            Config::getInstance()->mail = $mail;

            $general = Config::getInstance()->General;
            $general['noreply_email_name'] = $request->getStringParameter('mailFromName', '');

            $mailFrom = $request->getStringParameter('mailFromAddress', '');
            if (empty($mailFrom)) {
                $mailFrom = 'noreply@{DOMAIN}';
            }
            if (!Matomo::isValidEmailString($mailFrom) && !Common::stringEndsWith($mailFrom, '@{DOMAIN}')) {
                throw new Exception(Matomo::translate('CoreAdminHome_ErrorEmailFromAddressNotValid'));
            }
            $general['noreply_email_address'] = $mailFrom;
            Config::getInstance()->General = $general;

            Config::getInstance()->forceSave();

            $toReturn = $response->getResponse();
        } catch (Exception $e) {
            $toReturn = $response->getResponseException($e);
        }

        return $toReturn;
    }

    /**
     * Renders and returns an admin page that lets users generate custom JavaScript
     * tracking code and custom image tracker links.
     */
    public function trackingCodeGenerator()
    {
        Matomo::checkUserHasSomeViewAccess();

        $view = new View('@CoreAdminHome/trackingCodeGenerator');
        $this->setBasicVariablesView($view);
        $view->topMenu  = MenuTop::getInstance()->getMenu();

        $viewableIdSites = APISitesManager::getInstance()->getSitesIdWithAtLeastViewAccess();

        $defaultIdSite = reset($viewableIdSites);
        $view->idSite = $this->idSite ?: $defaultIdSite;

        if ($view->idSite) {
            try {
                $view->siteName = Site::getNameFor($view->idSite);
                $view->siteNameDecoded = Common::unsanitizeInputValue($view->siteName);
            } catch (Exception $e) {
                // ignore if site no longer exists
            }
        }

        $view->defaultReportSiteName = Site::getNameFor($view->idSite);
        $view->defaultSiteRevenue = Site::getCurrencySymbolFor($view->idSite);
        $view->maxCustomVariables = 0;

        if (Plugin\Manager::getInstance()->isPluginActivated('CustomVariables')) {
            $view->maxCustomVariables = CustomVariables::getNumUsableCustomVariables();
        }

        $view->defaultSite = array('id' => $view->idSite, 'name' => $view->defaultReportSiteName);
        $view->defaultSiteDecoded = [
            'id' => $view->idSite,
            'name' => Common::unsanitizeInputValue($view->defaultReportSiteName),
        ];

        $allUrls = APISitesManager::getInstance()->getSiteUrlsFromId($view->idSite);
        if (isset($allUrls[1])) {
            $aliasUrl = $allUrls[1];
        } else {
            $aliasUrl = 'x.domain.com';
        }
        $view->defaultReportSiteAlias = $aliasUrl;

        $mainUrl = Site::getMainUrlFor($view->idSite);
        $view->defaultReportSiteDomain = @parse_url($mainUrl, PHP_URL_HOST);

        $dntChecker = new DoNotTrackHeaderChecker();
        $view->serverSideDoNotTrackEnabled = $dntChecker->isActive();

        return $view->render();
    }

    /**
     * Shows the "Track Visits" checkbox - iFrame (deprecated)
     */
    public function optOut()
    {
        return $this->optOutManager->getOptOutViewIframe()->render();
    }

    /**
     * Shows the Javascript opt out
     *
     * @throws Exception
     */
    public function optOutJS(): string
    {
        Common::sendHeader('Content-Type: application/javascript; charset=utf-8');
        Common::sendHeader('Cache-Control: no-store');
        return $this->optOutManager->getOptOutJS();
    }

    #[JsonResponse]
    public function uploadCustomLogo(): string
    {
        Matomo::checkUserHasSuperUserAccess();
        $this->checkTokenInUrl();

        $logo = new CustomLogo();

        if (! $logo->isCustomLogoFeatureEnabled()) {
            return '0';
        }

        $successLogo    = $logo->uploadLogoToTempFolder();
        $successFavicon = $logo->uploadFaviconToTempFolder();

        $response = [];

        if ($successLogo) {
            $response['logo'] = $logo->getTempUserLogoBase64();
        }

        if ($successFavicon) {
            $response['favicon'] = $logo->getTempUserFaviconBase64();
        }

        return json_encode($response);
    }

    public static function isGeneralSettingsAdminEnabled()
    {
        return (bool) Config::getInstance()->General['enable_general_settings_admin'];
    }

    private function handleGeneralSettingsAdmin($view)
    {
        // Whether to display or not the general settings (cron, beta, smtp)
        $view->isGeneralSettingsAdminEnabled = self::isGeneralSettingsAdminEnabled();
        $view->isMultiServerEnvironment = SettingsPiwik::isMultiServerEnvironment();
        $view->isPluginsAdminEnabled = CorePluginsAdmin::isPluginsAdminEnabled();
        if ($view->isGeneralSettingsAdminEnabled) {
            $this->displayWarningIfConfigFileNotWritable();
        }

        $enableBrowserTriggerArchiving = Rules::isBrowserTriggerEnabled();
        $todayArchiveTimeToLive = Rules::getTodayArchiveTimeToLive();
        $showWarningCron = false;
        if (
            !$enableBrowserTriggerArchiving
            && $todayArchiveTimeToLive < 3600
        ) {
            $showWarningCron = true;
        }
        $view->showWarningCron = $showWarningCron;
        $view->todayArchiveTimeToLive = $todayArchiveTimeToLive;
        $view->todayArchiveTimeToLiveDefault = Rules::getTodayArchiveTimeToLiveDefault();
        $view->enableBrowserTriggerArchiving = $enableBrowserTriggerArchiving;
        $view->showSegmentArchiveTriggerInfo = Rules::isBrowserArchivingAvailableForSegments();

        $mail = Config::getInstance()->mail;
        $mail['noreply_email_address'] = Config::getInstance()->General['noreply_email_address'];
        $mail['noreply_email_name'] = Config::getInstance()->General['noreply_email_name'];
        $mail['password'] = !empty($mail['password']) ? '*****' : '';
        $view->mail = $mail;
    }

    /**
     * Show the what is new changes list
     */
    public function whatIsNew()
    {
        Matomo::checkUserHasSomeViewAccess();
        Matomo::checkUserIsNotAnonymous();

        $model = new UsersModel();
        $user = $model->getUser(Matomo::getCurrentUserLogin());
        if (!empty($user)) {
            $userChanges = new UserChanges($user);
            $changes = $this->enrichChangesForWhatIsNew($userChanges->getChanges());
            return $this->renderTemplate('whatIsNew', ['changes' => $changes]);
        } else {
            throw new \Exception('Unable to getUser() when attempting to show whatIsNew');
        }
    }

    /**
     * Adds metadata used for rendering entries in the What's New popup.
     */
    private function enrichChangesForWhatIsNew(array $changes): array
    {
        $pluginManager = Plugin\Manager::getInstance();

        foreach ($changes as &$change) {
            $pluginName = $change['plugin_name'] ?? '';
            $change['showPluginPrefix'] = !empty($pluginName)
                && !$pluginManager->isPluginBundledWithCore($pluginName);

            if (!empty($change['link'])) {
                $defaultIdSite = $this->getDefaultIdSiteForWhatIsNewLinks();
                if (!empty($defaultIdSite)) {
                    $change['link'] = $this->normalizeWhatIsNewLink($change['link'], $defaultIdSite);
                }
            }
        }
        unset($change);

        return $changes;
    }

    private function getDefaultIdSiteForWhatIsNewLinks(): ?int
    {
        $userPreferences = new UserPreferences();
        $defaultReport = $userPreferences->getDefaultReport();

        if (is_numeric($defaultReport)) {
            return (int) $defaultReport;
        }

        $defaultWebsiteId = $userPreferences->getDefaultWebsiteId();
        if (is_numeric($defaultWebsiteId)) {
            return (int) $defaultWebsiteId;
        }

        return null;
    }

    private function normalizeWhatIsNewLink(string $link, int $defaultIdSite): string
    {
        if (!$this->isInternalWhatIsNewLink($link)) {
            return $link;
        }

        // Use a path relative to the current Matomo install so subdirectory installs
        // don't resolve internal "What's New" links against the web root.
        if (strpos($link, '/index.php') === 0) {
            $link = substr($link, 1);
        }

        $parsedLink = @parse_url($link);
        if (!is_array($parsedLink)) {
            return $link;
        }

        $query = $parsedLink['query'] ?? '';
        $parsedLink['query'] = $this->replaceIdSiteInQueryString($query, $defaultIdSite);

        $fragment = $parsedLink['fragment'] ?? '';
        $parsedLink['fragment'] = $this->replaceIdSiteInFragment($fragment, $defaultIdSite);

        if ($query === $parsedLink['query'] && $fragment === $parsedLink['fragment']) {
            return $link;
        }

        return UrlHelper::getParseUrlReverse($parsedLink) ?: $link;
    }

    private function replaceIdSiteInFragment(string $fragment, int $idSite): string
    {
        $fragmentPrefix = '';
        $fragmentQuery = '';
        if (strpos($fragment, '/?') === 0) {
            $fragmentPrefix = '/?';
            $fragmentQuery = substr($fragment, 2);
        } elseif (strpos($fragment, '?') === 0) {
            $fragmentPrefix = '?';
            $fragmentQuery = substr($fragment, 1);
        }

        if (empty($fragmentPrefix)) {
            return $fragment;
        }

        return $fragmentPrefix . $this->replaceIdSiteInQueryString($fragmentQuery, $idSite);
    }

    private function replaceIdSiteInQueryString(string $queryStr, int $idSite): string
    {
        $queryParams = UrlHelper::getArrayFromQueryString($queryStr);

        if (!array_key_exists('idSite', $queryParams)) {
            return $queryStr;
        }

        $queryParams['idSite'] = $idSite;
        return Url::getQueryStringFromParameters($queryParams);
    }

    private function isInternalWhatIsNewLink(string $link): bool
    {
        return strpos($link, 'index.php') === 0 || strpos($link, '/index.php') === 0;
    }
}
