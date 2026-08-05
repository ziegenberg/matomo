<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo;

use Exception;
use Matomo\API\Request;
use Matomo\Exception\PluginNotFoundException;
use Matomo\Http\HttpCodeException;
use Matomo\Request\AuthenticationToken;
use Matomo\Config\GeneralConfig;
use Matomo\Container\StaticContainer;
use Matomo\DataTable\Manager;
use Matomo\DataTable\Renderer\Json;
use Matomo\Exception\AuthenticationFailedException;
use Matomo\Exception\DatabaseSchemaIsNewerThanCodebaseException;
use Matomo\Exception\PluginDeactivatedException;
use Matomo\Exception\PluginRequiresInternetException;
use Matomo\Exception\StylesheetLessCompileException;
use Matomo\Http\ControllerResolver;
use Matomo\Http\JsonResponse;
use Matomo\Http\Router;
use Matomo\Plugins\CoreAdminHome\CustomLogo;
use Matomo\Session\SessionAuth;
use Matomo\Session\SessionInitializer;
use Matomo\Log\LoggerInterface;

/**
 * This singleton dispatches requests to the appropriate plugin Controller.
 *
 * Piwik uses this class for all requests that go through **index.php**. Plugins can
 * use it to call controller actions of other plugins.
 *
 * ### Examples
 *
 * **Forwarding controller requests**
 *
 *     public function myConfiguredRealtimeMap()
 *     {
 *         $_GET['changeVisitAlpha'] = false;
 *         $_GET['removeOldVisits'] = false;
 *         $_GET['showFooterMessage'] = false;
 *         return FrontController::getInstance()->dispatch('UserCountryMap', 'realtimeMap');
 *     }
 *
 * **Using other plugin controller actions**
 *
 *     public function myPopupWithRealtimeMap()
 *     {
 *         $_GET['changeVisitAlpha'] = false;
 *         $_GET['removeOldVisits'] = false;
 *         $_GET['showFooterMessage'] = false;
 *         $realtimeMap = FrontController::getInstance()->dispatch('UserCountryMap', 'realtimeMap');
 *
 *         $view = new View('@MyPlugin/myPopupWithRealtimeMap.twig');
 *         $view->realtimeMap = $realtimeMap;
 *         return $realtimeMap->render();
 *     }
 *
 * For a detailed explanation, see the documentation [here](https://developer.matomo.org/guides/how-piwik-works).
 *
 * @method static \Matomo\FrontController getInstance()
 */
class FrontController extends Singleton
{
    public const DEFAULT_MODULE = 'CoreHome';
    public const DEFAULT_LOGIN = 'anonymous';
    public const DEFAULT_TOKEN_AUTH = 'anonymous';
    private const SESSION_TIMEOUT_COOKIE_NAME = 'matomo_session_timed_out';

    // public for tests
    public static $requestId = null;

    /**
     * Set to false and the Front Controller will not dispatch the request
     *
     * @var bool
     */
    public static $enableDispatch = true;

    private bool $initialized = false;

    /**
     * @param $lastError
     * @return string
     * @throws AuthenticationFailedException
     * @throws Exception
     */
    private static function generateSafeModeOutputFromError($lastError)
    {
        Common::sendResponseCode(500);

        $controller = FrontController::getInstance();
        try {
            $controller->init();
            $message = $controller->dispatch('CorePluginsAdmin', 'safemode', array($lastError));
        } catch (Exception $e) {
            // may fail in safe mode (eg. global.ini.php not found)
            $message = sprintf("Matomo encountered an error: %s (which lead to: %s)", $lastError['message'], $e->getMessage());
        }

        return $message;
    }

    /**
     * @param Exception $e
     * @return string
     */
    public static function generateSafeModeOutputFromException($e)
    {
        if ($e instanceof HttpCodeException && $e->getCode() >= 400 && $e->getCode() < 500) {
            StaticContainer::get(LoggerInterface::class)->debug('Uncaught client error: {exception}', [
                'exception'            => $e,
                'ignoreInScreenWriter' => true,
            ]);
        } else {
            StaticContainer::get(LoggerInterface::class)->error('Uncaught exception: {exception}', [
                'exception'            => $e,
                'ignoreInScreenWriter' => true,
            ]);
        }

        $error = array(
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        );

        if (isset(self::$requestId)) {
            $error['request_id'] = self::$requestId;
        }

        $error['backtrace'] = ' on ' . $error['file'] . '(' . $error['line'] . ")\n";
        $error['backtrace'] .= $e->getTraceAsString();

        $exception = $e;
        while ($exception = $exception->getPrevious()) {
            $error['backtrace'] .= "\ncaused by: " . $exception->getMessage();
            $error['backtrace'] .= ' on ' . $exception->getFile() . '(' . $exception->getLine() . ")\n";
            $error['backtrace'] .= $exception->getTraceAsString();
        }

        return self::generateSafeModeOutputFromError($error);
    }

    /**
     * Executes the requested plugin controller method.
     *
     * @throws Exception|\Matomo\Exception\PluginDeactivatedException in case the plugin doesn't exist, the action doesn't exist,
     *                                                     there is not enough permission, etc.
     *
     * @param string $module The name of the plugin whose controller to execute, eg, `'UserCountryMap'`.
     * @param string $action The controller method name, eg, `'realtimeMap'`.
     * @param array $parameters Array of parameters to pass to the controller method.
     * @return void|mixed The returned value of the call. This is the output of the controller method.
     * @api
     */
    public function dispatch($module = null, $action = null, $parameters = null)
    {
        if (self::$enableDispatch === false) {
            return;
        }

        $filter = new Router();
        $redirection = $filter->filterUrl(Url::getCurrentUrl());
        if ($redirection !== null) {
            Url::redirectToUrl($redirection);
            return;
        }

        try {
            $result = $this->doDispatch($module, $action, $parameters);
            return $result;
        } catch (NoAccessException $exception) {
            Log::debug($exception);

            /**
             * Triggered when a user with insufficient access permissions tries to view some resource.
             *
             * This event can be used to customize the error that occurs when a user is denied access
             * (for example, displaying an error message, redirecting to a page other than login, etc.).
             *
             * @param \Matomo\NoAccessException $exception The exception that was caught.
             */
            Matomo::postEvent('User.isNotAuthorized', array($exception), $pending = true);
        } catch (\Twig\Error\RuntimeError $e) {
            if ($e->getPrevious() && !$e->getPrevious() instanceof \Twig\Error\RuntimeError) {
                // a regular exception unrelated to twig was triggered while rendering an a view, for example as part of a triggered event
                // we want to ensure to show the regular error message response instead of the safemode as it's likely wrong user input
                throw $e;
            } else {
                echo $this->generateSafeModeOutputFromException($e);
                exit;
            }
        } catch (StylesheetLessCompileException $e) {
            echo $this->generateSafeModeOutputFromException($e);
            exit;
        } catch (\Error $e) {
            echo $this->generateSafeModeOutputFromException($e);
            exit;
        }
    }

    /**
     * Executes the requested plugin controller method and returns the data, capturing anything the
     * method `echo`s.
     *
     * _Note: If the plugin controller returns something, the return value is returned instead
     * of whatever is in the output buffer._
     *
     * @param string $module The name of the plugin whose controller to execute, eg, `'UserCountryMap'`.
     * @param string $actionName The controller action name, eg, `'realtimeMap'`.
     * @param array $parameters Array of parameters to pass to the controller action method.
     * @return mixed The `echo`'d data (as a string) or the return value of the controller action.
     */
    public function fetchDispatch($module = null, $actionName = null, $parameters = null)
    {
        ob_start();
        $output = $this->dispatch($module, $actionName, $parameters);
        // if nothing returned we try to load something that was printed on the screen
        if (empty($output)) {
            $output = ob_get_contents();
        } else {
            // if something was returned, flush output buffer as it is meant to be written to the screen
            ob_flush();
        }
        ob_end_clean();
        return $output;
    }

    /**
     * Called at the end of the page generation
     */
    public function __destruct()
    {
        try {
            if (
                class_exists('Matomo\Profiler')
                && !SettingsServer::isTrackerApiRequest()
            ) {
                // in tracker mode Piwik\Tracker\Db\Pdo\Mysql does currently not implement profiling
                Profiler::displayDbProfileReport();
                Profiler::printQueryCount();
            }
        } catch (Exception $e) {
            Log::debug($e);
        }
    }

    // Should we show exceptions messages directly rather than display an html error page?
    public static function shouldRethrowException()
    {
        // If we are in no dispatch mode, eg. a script reusing Piwik libs,
        // then we should return the exception directly, rather than trigger the event "bad config file"
        // which load the HTML page of the installer with the error.
        return (defined('PIWIK_ENABLE_DISPATCH') && !PIWIK_ENABLE_DISPATCH)
        || Common::isPhpCliMode()
        || SettingsServer::isArchivePhpTriggered();
    }

    public static function setUpSafeMode()
    {
        register_shutdown_function(array('\Matomo\FrontController', 'triggerSafeModeWhenError'));
    }

    public static function triggerSafeModeWhenError()
    {
        Manager::getInstance()->deleteAll();

        $lastError = error_get_last();

        if (!empty($lastError) && isset(self::$requestId)) {
            $lastError['request_id'] = self::$requestId;
        }

        if (!empty($lastError) && $lastError['type'] == E_ERROR) {
            $lastError['backtrace'] = ' on ' . $lastError['file'] . '(' . $lastError['line'] . ")\n"
                . ErrorHandler::getFatalErrorPartialBacktrace();

            StaticContainer::get(LoggerInterface::class)->error('Fatal error encountered: {exception}', [
                'exception' => $lastError,
                'ignoreInScreenWriter' => true,
            ]);

            $message = self::generateSafeModeOutputFromError($lastError);
            echo $message;
        }
    }

    /**
     * Must be called before dispatch()
     * - checks that directories are writable,
     * - loads the configuration file,
     * - loads the plugin,
     * - inits the DB connection,
     * - etc.
     *
     * @throws Exception
     * @return void
     */
    public function init()
    {
        if ($this->initialized) {
            return;
        }

        self::setRequestIdHeader();

        $this->initialized = true;

        $tmpPath = StaticContainer::get('path.tmp');

        $directoriesToCheck = array(
            $tmpPath,
            $tmpPath . '/assets/',
            $tmpPath . '/cache/',
            $tmpPath . '/logs/',
            $tmpPath . '/tcpdf/',
            StaticContainer::get('path.tmp.templates'),
        );

        Filechecks::dieIfDirectoriesNotWritable($directoriesToCheck);

        $this->handleMaintenanceMode();
        $this->handleProfiler();
        $this->handleSSLRedirection();

        Plugin\Manager::getInstance()->loadPluginTranslations();
        Plugin\Manager::getInstance()->loadActivatedPlugins();

        // try to connect to the database
        try {
            Db::createDatabaseObject();
            Db::fetchAll("SELECT DATABASE()");
        } catch (Exception $exception) {
            if (self::shouldRethrowException()) {
                throw $exception;
            }

            Log::debug($exception);

            /**
             * Triggered when Piwik cannot connect to the database.
             *
             * This event can be used to start the installation process or to display a custom error
             * message.
             *
             * @param Exception $exception The exception thrown from creating and testing the database
             *                             connection.
             */
            Matomo::postEvent('Db.cannotConnectToDb', array($exception), $pending = true);

            throw $exception;
        }

        // try to get an option (to check if data can be queried)
        try {
            Option::get('TestingIfDatabaseConnectionWorked');
        } catch (Exception $exception) {
            if (self::shouldRethrowException()) {
                throw $exception;
            }

            Log::debug($exception);

            /**
             * Triggered when Piwik cannot access database data.
             *
             * This event can be used to start the installation process or to display a custom error
             * message.
             *
             * @param Exception $exception The exception thrown from trying to get an option value.
             */
            Matomo::postEvent('Config.badConfigurationFile', array($exception), $pending = true);

            throw $exception;
        }

        // Init the Access object, so that eg. core/Updates/* can enforce Super User and use some APIs
        Access::getInstance();

        /**
         * Triggered just after the platform is initialized and plugins are loaded.
         *
         * This event can be used to do early initialization.
         *
         * _Note: At this point the user is not authenticated yet._
         */
        Matomo::postEvent('Request.dispatchCoreAndPluginUpdatesScreen');

        $this->throwIfPiwikVersionIsOlderThanDBSchema();

        $module = Matomo::getModule();
        $action = Matomo::getAction();

        if (
            empty($module)
            || empty($action)
            || $module !== 'Installation'
            || !in_array($action, array('getInstallationCss', 'getInstallationJs'))
        ) {
            \Matomo\Plugin\Manager::getInstance()->installLoadedPlugins();
        }

        // ensure the current Piwik URL is known for later use
        if (method_exists('Matomo\SettingsPiwik', 'getPiwikUrl')) {
            SettingsPiwik::getPiwikUrl();
        }

        $loggedIn = false;

        //move this up unsupported Browser do not create session
        if ($this->isSupportedBrowserCheckNeeded()) {
            SupportedBrowser::checkIfBrowserSupported();
        }

        // don't use sessionauth in cli mode
        // try authenticating w/ session first...
        $sessionAuth = $this->makeSessionAuthenticator();
        if ($sessionAuth) {
            $loggedIn = Access::getInstance()->reloadAccess($sessionAuth);
            if (!$loggedIn && $sessionAuth->wasSessionExpired()) {
                Access::getInstance()->setSessionExpired(true);
            }
        }

        // ... if session auth fails try normal auth (which will login the anonymous user)
        if (!$loggedIn) {
            $authAdapter = $this->makeAuthenticator();
            $success = Access::getInstance()->reloadAccess($authAdapter);

            if (
                $success
                && Matomo::isUserIsAnonymous()
                && $authAdapter->getLogin() === 'anonymous' //double checking the login
                && Matomo::isUserHasSomeViewAccess()
                && Session::isSessionStarted()
                && Session::isWritable() // only if session was started and writable, don't do it eg for API
            ) {
                // usually the session would be started when someone logs in using login controller. But in this
                // case we need to init session here for anoynymous users
                $init = StaticContainer::get(SessionInitializer::class);
                $init->initSession($authAdapter);
            }
        } else {
            $this->makeAuthenticator($sessionAuth); // Piwik\Auth must be set to the correct Login plugin
        }

        $this->consumeSessionTimeoutCookie();
        $this->sendSessionTimedOutHeaderIfNeeded();

        // Force the auth to use the token_auth if specified, so that embed dashboard
        // and all other non widgetized controller methods works fine
        if (
            StaticContainer::get(AuthenticationToken::class)->getAuthToken() !== ''
            && Request::shouldReloadAuthUsingTokenAuth(null)
        ) {
            Request::reloadAuthUsingTokenAuth();
            Request::checkTokenAuthIsNotLimited($module, $action);
        }

        SettingsServer::raiseMemoryLimitIfNecessary();

        \Matomo\Plugin\Manager::getInstance()->postLoadPlugins();

        /**
         * Triggered after the platform is initialized and after the user has been authenticated, but
         * before the platform has handled the request.
         *
         * Piwik uses this event to check for updates to Piwik.
         */
        Matomo::postEvent('Platform.initialized');
    }

    protected function prepareDispatch($module, $action, $parameters)
    {
        if (is_null($module)) {
            $module = Common::getRequestVar('module', self::DEFAULT_MODULE, 'string');
        }

        if (is_null($action)) {
            $action = Common::getRequestVar('action', false);
            if ($action !== false) {
                // If a value was provided, check it has the correct type.
                $action = Common::getRequestVar('action', null, 'string');
            }
        }

        if (Session::isSessionStarted()) {
            $this->closeSessionEarlyForFasterUI();
        }

        if (is_null($parameters)) {
            $parameters = array();
        }

        if (!ctype_alnum($module)) {
            throw new Exception("Invalid module name '$module'");
        }

        [$module, $action] = Request::getRenamedModuleAndAction($module, $action);

        if (!SettingsPiwik::isInternetEnabled() && \Matomo\Plugin\Manager::getInstance()->doesPluginRequireInternetConnection($module)) {
            throw new PluginRequiresInternetException($module);
        }

        if (!\Matomo\Plugin\Manager::getInstance()->isPluginInFilesystem($module)) {
            throw new PluginNotFoundException($module);
        }

        if (!\Matomo\Plugin\Manager::getInstance()->isPluginActivated($module)) {
            throw new PluginDeactivatedException($module);
        }

        return array($module, $action, $parameters);
    }

    protected function handleMaintenanceMode()
    {
        if ((GeneralConfig::getConfigValue('maintenance_mode') != 1) || Common::isPhpCliMode()) {
            return;
        }

        // as request matomo behind load balancer should not return 503. https://github.com/matomo-org/matomo/issues/18054
        if (GeneralConfig::getConfigValue('multi_server_environment') != 1) {
            Common::sendResponseCode(503);
        }

        $logoUrl = 'plugins/Morpheus/images/logo.svg';
        $faviconUrl = 'plugins/CoreHome/images/favicon.png';
        try {
            $logo = new CustomLogo();
            if ($logo->hasSVGLogo()) {
                $logoUrl = $logo->getSVGLogoUrl();
            } else {
                $logoUrl = $logo->getHeaderLogoUrl();
            }
            $faviconUrl = $logo->getPathUserFavicon();
        } catch (Exception $ex) {
        }

        $recordStatistics = Config::getInstance()->Tracker['record_statistics'];
        $trackMessage = '';

        if ($recordStatistics) {
            $trackMessage = 'Your analytics data will continue to be tracked as normal.';
        } else {
            $trackMessage = 'While the maintenance mode is active, data tracking is disabled.';
        }

        $page = file_get_contents(PIWIK_INCLUDE_PATH . '/plugins/Morpheus/templates/maintenance.tpl');
        $page = str_replace('%logoUrl%', $logoUrl, $page);
        $page = str_replace('%faviconUrl%', $faviconUrl, $page);
        $page = str_replace('%piwikTitle%', Matomo::getRandomTitle(), $page);

        $page = str_replace('%trackMessage%', $trackMessage, $page);

        echo $page;
        exit;
    }

    protected function handleSSLRedirection()
    {
        // Specifically disable for the opt out iframe
        if (Matomo::getModule() == 'CoreAdminHome' && (Matomo::getAction() == 'optOut' || Matomo::getAction() == 'optOutJS')) {
            return;
        }
        // Disable Https for VisitorGenerator
        if (Matomo::getModule() == 'VisitorGenerator') {
            return;
        }
        if (Common::isPhpCliMode()) {
            return;
        }
        // proceed only when force_ssl = 1
        if (!SettingsPiwik::isHttpsForced()) {
            return;
        }
        // TODO: remove in Matomo 6 - avoid update redirect loops before proxy_scheme_headers migration runs.
        if (Matomo::getModule() === 'CoreUpdater' && ProxyHeaders::getProtocolInformation() !== null) {
            return;
        }
        Url::redirectToHttps();
    }

    private function closeSessionEarlyForFasterUI()
    {
        $isDashboardReferrer = !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'module=CoreHome&action=index') !== false;
        $isAllWebsitesReferrer = !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'module=MultiSites&action=index') !== false;

        if (
            $isDashboardReferrer
            && StaticContainer::get(AuthenticationToken::class)->wasTokenAuthProvidedSecurely()
            && Common::getRequestVar('widget', 0, 'int') === 1
        ) {
            Session::close();
        }

        if (
            ($isDashboardReferrer || $isAllWebsitesReferrer)
            && Common::getRequestVar('viewDataTable', '', 'string') === 'sparkline'
        ) {
            Session::close();
        }
    }

    private function handleProfiler()
    {
        $profilerEnabled = Config::getInstance()->Debug['enable_php_profiler'] == 1;
        if (!$profilerEnabled) {
            return;
        }

        if (!empty($_GET['xhprof'])) {
            $mainRun = $_GET['xhprof'] == 1; // core:archive command sets xhprof=2
            Profiler::setupProfilerXHProf($mainRun);
        }
    }

    /**
     * @param $module
     * @param $action
     * @param $parameters
     * @return mixed
     */
    private function doDispatch($module, $action, $parameters)
    {
        [$module, $action, $parameters] = $this->prepareDispatch($module, $action, $parameters);

        /**
         * Triggered directly before controller actions are dispatched.
         *
         * This event can be used to modify the parameters passed to one or more controller actions
         * and can be used to change the controller action being dispatched to.
         *
         * @param string &$module The name of the plugin being dispatched to.
         * @param string &$action The name of the controller method being dispatched to.
         * @param array &$parameters The arguments passed to the controller action.
         */
        Matomo::postEvent('Request.dispatch', array(&$module, &$action, &$parameters));

        /** @var ControllerResolver $controllerResolver */
        $controllerResolver = StaticContainer::get('Matomo\Http\ControllerResolver');

        $controller = $controllerResolver->getController($module, $action, $parameters);

        /**
         * Triggered directly before controller actions are dispatched.
         *
         * This event exists for convenience and is triggered directly after the {@hook Request.dispatch}
         * event is triggered.
         *
         * It can be used to do the same things as the {@hook Request.dispatch} event, but for one controller
         * action only. Using this event will result in a little less code than {@hook Request.dispatch}.
         *
         * @param array &$parameters The arguments passed to the controller action.
         */
        Matomo::postEvent(sprintf('Controller.%s.%s', $module, $action), array(&$parameters));

        $result = call_user_func_array($controller, $parameters);

        /**
         * Triggered after a controller action is successfully called.
         *
         * This event exists for convenience and is triggered immediately before the {@hook Request.dispatch.end}
         * event is triggered.
         *
         * It can be used to do the same things as the {@hook Request.dispatch.end} event, but for one
         * controller action only. Using this event will result in a little less code than
         * {@hook Request.dispatch.end}.
         *
         * @param mixed &$result The result of the controller action.
         * @param array $parameters The arguments passed to the controller action.
         */
        Matomo::postEvent(sprintf('Controller.%s.%s.end', $module, $action), array(&$result, $parameters));

        /**
         * Triggered after a controller action is successfully called.
         *
         * This event can be used to modify controller action output (if any) before the output is returned.
         *
         * @param mixed &$result The controller action result.
         * @param array $parameters The arguments passed to the controller action.
         */
        Matomo::postEvent('Request.dispatch.end', array(&$result, $module, $action, $parameters));

        $this->applyResponseHeadersFromAttributes($controller);

        return $result;
    }

    /**
     * Applies response headers declared via attributes on the dispatched controller action.
     *
     * This runs after the action has fully returned (and after the dispatch events), so re-sending
     * the header here guarantees it wins over anything the action did while building its response,
     * such as rendering a View that resets the Content-Type to text/html.
     *
     * @param callable $controller The resolved controller action, an [$object, $method] callable.
     */
    private function applyResponseHeadersFromAttributes(callable $controller): void
    {
        // Only array callables built as [$controllerObject, $actionName] can carry an action attribute.
        if (!is_array($controller) || !is_object($controller[0])) {
            return;
        }

        $method = new \ReflectionMethod($controller[0], $controller[1]);

        if (count($method->getAttributes(JsonResponse::class)) > 0) {
            Json::sendHeaderJSON();
        }
    }

    /**
     * This method ensures that Piwik Platform cannot be running when using a NEWER database.
     */
    private function throwIfPiwikVersionIsOlderThanDBSchema()
    {
        // When developing this situation happens often when switching branches
        if (Development::isEnabled()) {
            return;
        }

        if (!StaticContainer::get('EnableDbVersionCheck')) {
            return;
        }

        $updater = new Updater();

        $dbSchemaVersion = $updater->getCurrentComponentVersion('core');
        $current = Version::VERSION;
        if (-1 === version_compare($current, $dbSchemaVersion)) {
            $messages = array(
                Matomo::translate('General_ExceptionDatabaseVersionNewerThanCodebase', array($current, $dbSchemaVersion)),
                Matomo::translate('General_ExceptionDatabaseVersionNewerThanCodebaseWait'),
                // we cannot fill in the Super User emails as we are failing before Authentication was ready
                Matomo::translate('General_ExceptionContactSupportGeneric', array('', '')),
            );
            throw new DatabaseSchemaIsNewerThanCodebaseException(implode(" ", $messages));
        }
    }

    private function makeSessionAuthenticator()
    {
        if (
            Common::isPhpClimode()
            && !defined('PIWIK_TEST_MODE')
        ) { // don't use the session auth during CLI requests
            return null;
        }

        $token = StaticContainer::get(AuthenticationToken::class);

        if ($token->getAuthToken() !== '' && !$token->isSessionToken()) {
             return null;
        }

        $module = Common::getRequestVar('module', self::DEFAULT_MODULE, 'string');
        $action = Common::getRequestVar('action', false);

        // the session must be started before using the session authenticator,
        // so we do it here, if this is not an API request.
        if (
            SettingsPiwik::isMatomoInstalled()
            && ($module !== 'API' || ($action && $action !== 'index'))
            && !($module === 'CoreAdminHome' && $action === 'optOutJS')
        ) {
            /**
             * @ignore
             */
            Matomo::postEvent('Session.beforeSessionStart');

            Session::start();
            return StaticContainer::get(SessionAuth::class);
        }

        return null;
    }

    private function makeAuthenticator(?SessionAuth $auth = null)
    {
        /**
         * Triggered before the user is authenticated, when the global authentication object
         * should be created.
         *
         * Plugins that provide their own authentication implementation should use this event
         * to set the global authentication object (which must derive from {@link Matomo\Auth}).
         *
         * **Example**
         *
         *     Piwik::addAction('Request.initAuthenticationObject', function() {
         *         StaticContainer::getContainer()->set('Matomo\Auth', new MyAuthImplementation());
         *     });
         */
        Matomo::postEvent('Request.initAuthenticationObject');
        try {
            $authAdapter = StaticContainer::get('Matomo\Auth');
        } catch (Exception $e) {
            $message = "Authentication object cannot be found in the container. Maybe the Login plugin is not activated?
                        <br />You can activate the plugin by adding:<br />
                        <code>Plugins[] = Login</code><br />
                        under the <code>[Plugins]</code> section in your config/config.ini.php";

            $ex = new AuthenticationFailedException($message);
            $ex->setIsHtmlMessage();

            throw $ex;
        }

        if ($auth) {
            $authAdapter->setLogin($auth->getLogin());
            $authAdapter->setTokenAuth($auth->getTokenAuth());
        } else {
            $authAdapter->setLogin(self::DEFAULT_LOGIN);
            $authAdapter->setTokenAuth(self::DEFAULT_TOKEN_AUTH);
        }

        return $authAdapter;
    }

    public static function getUniqueRequestId()
    {
        if (self::$requestId === null) {
            self::$requestId = substr(Common::generateUniqId(), 0, 5);
        }
        return self::$requestId;
    }

    private static function setRequestIdHeader()
    {
        $requestId = self::getUniqueRequestId();
        Common::sendHeader("X-Matomo-Request-Id: $requestId");
    }

    private function consumeSessionTimeoutCookie(): void
    {
        $cookie = new Cookie(self::SESSION_TIMEOUT_COOKIE_NAME);

        if (!$cookie->isCookieFound()) {
            return;
        }

        $cookie->delete();

        if (Matomo::isUserIsAnonymous()) {
            Access::getInstance()->setSessionExpired(true);
        }
    }

    private function isSupportedBrowserCheckNeeded()
    {
        if (defined('PIWIK_ENABLE_DISPATCH') && !PIWIK_ENABLE_DISPATCH) {
            return false;
        }

        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($userAgent === '') {
            return false;
        }

        $isTestMode = defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE;
        if (!$isTestMode && Common::isPhpCliMode() === true) {
            return false;
        }

        if (Matomo::getModule() === 'API' && (empty(Matomo::getAction()) || Matomo::getAction() === 'index' || Matomo::getAction() === 'glossary')) {
            return false;
        }

        if (Matomo::getModule() === 'Widgetize') {
            return true;
        }

        $generalConfig = Config::getInstance()->General;
        if ($generalConfig['enable_framed_pages'] == '1' || $generalConfig['enable_framed_settings'] == '1') {
            return true;
        }

        if (StaticContainer::get(AuthenticationToken::class)->getAuthToken() !== '') {
            return true;
        }

        if (Matomo::isUserIsAnonymous()) {
            return true;
        }

        return false;
    }

    private function sendSessionTimedOutHeaderIfNeeded()
    {
        if (!Access::getInstance()->wasSessionExpired()) {
            return;
        }
        Common::sendHeader('X-Matomo-Session-Timed-Out: 1');
    }
}
