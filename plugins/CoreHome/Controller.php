<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreHome;

use Exception;
use Matomo\API\Request;
use Matomo\Category\CategoryList;
use Matomo\Common;
use Matomo\Config;
use Matomo\Container\StaticContainer;
use Matomo\Http\JsonResponse;
use Matomo\Date;
use Matomo\FrontController;
use Matomo\Log\LoggerInterface;
use Matomo\Notification\Manager as NotificationManager;
use Matomo\Matomo;
use Matomo\Plugin\Report;
use Matomo\Plugins\FeatureFlags\FeatureFlagManager;
use Matomo\Plugins\FeatureFlags\FeatureFlags\Example;
use Matomo\Plugins\FeatureFlags\Storage\ConfigFeatureFlagStorage;
use Matomo\Plugins\Marketplace\Marketplace;
use Matomo\SettingsPiwik;
use Matomo\Widget\Widget;
use Matomo\Plugins\CoreHome\DataTableRowAction\MultiRowEvolution;
use Matomo\Plugins\CoreHome\DataTableRowAction\RowEvolution;
use Matomo\Plugins\UsersManager\API;
use Matomo\Translation\Translator;
use Matomo\UpdateCheck;
use Matomo\Url;
use Matomo\View;
use Matomo\ViewDataTable\Manager as ViewDataTableManager;
use Matomo\Widget\WidgetConfig;

class Controller extends \Matomo\Plugin\Controller
{
    private Translator $translator;

    private FeatureFlagManager $featureFlagManager;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;

        parent::__construct();

        $this->featureFlagManager = new FeatureFlagManager(
            [new ConfigFeatureFlagStorage(Config::getInstance())],
            StaticContainer::get(LoggerInterface::class)
        );
    }

    public function getDefaultAction()
    {
        return 'redirectToCoreHomeIndex';
    }

    public function renderReportWidget(Report $report)
    {
        Matomo::checkUserHasSomeViewAccess();
        $this->checkSitePermission();

        $report->checkIsEnabled();

        return $report->render();
    }

    /**
     * Renders a widget container (used for dashboard/tab widget containers as well as
     * widgetized/exported embeds).
     * @return string
     */
    public function renderWidgetContainer()
    {
        Matomo::checkUserHasSomeViewAccess();
        $this->checkSitePermission();

        $view = new View('@CoreHome/widgetContainer');
        $view->isWidgetized = (bool) Common::getRequestVar('widget', 0, 'int');
        $view->containerId  = Common::getRequestVar('containerId', null, 'string');

        return $view->render();
    }

    /**
     * @param Widget $widget
     * @return mixed
     * @throws Exception
     */
    public function renderWidget($widget)
    {
        Matomo::checkUserHasSomeViewAccess();

        $config = new WidgetConfig();
        $widget::configure($config);

        $content = $widget->render();

        if ($config->getName() && Common::getRequestVar('showtitle', '', 'string') === '1') {
            if (
                strpos($content, '<h2') !== false
                || strpos($content, ' content-title=') !== false
                || strpos($content, 'CoreHome.EnrichedHeadline') !== false
                || strpos($content, 'CoreHome.ReportHeader') !== false
                || strpos($content, '<h1') !== false
            ) {
                // already includes title
                return $content;
            }

            if (
                strpos($content, '<!-- has-content-block -->') === false
                && strpos($content, 'class="card"') === false
                && strpos($content, "class='card'") === false
                && strpos($content, 'class="card-content"') === false
                && strpos($content, "class='card-content'") === false
            ) {
                $view = new View('@CoreHome/_singleWidget');
                $view->title = $config->getName();
                $view->content = $content;
                return $view->render();
            }
        }

        return $content;
    }

    public function redirectToCoreHomeIndex()
    {
        $defaultReport = API::getInstance()->getUserPreference(
            API::PREFERENCE_DEFAULT_REPORT,
            Matomo::getCurrentUserLogin()
        );
        $module = 'CoreHome';
        $action = 'index';

        // User preference: default report to load is the All Websites dashboard
        if (
            $defaultReport == 'MultiSites'
            && \Matomo\Plugin\Manager::getInstance()->isPluginActivated('MultiSites')
        ) {
            $module = 'MultiSites';
        }

        if ($defaultReport == Matomo::getLoginPluginName()) {
            $module = Matomo::getLoginPluginName();
        }

        parent::redirectToIndex($module, $action, $this->idSite);
    }

    public function showInContext()
    {
        $controllerName = Common::getRequestVar('moduleToLoad');
        $actionName     = Common::getRequestVar('actionToLoad', 'index');

        if ($controllerName == 'API') {
            throw new Exception("Showing API requests in context is not supported for security reasons. Please change query parameter 'moduleToLoad'.");
        }
        if ($actionName == 'showInContext') {
            throw new Exception("Preventing infinite recursion...");
        }

        $view = $this->getDefaultIndexView();
        $view->content = FrontController::getInstance()->fetchDispatch($controllerName, $actionName);
        return $view->render();
    }

    #[JsonResponse]
    public function markNotificationAsRead(): string
    {
        Matomo::checkUserHasSomeViewAccess();
        $this->checkTokenInUrl();

        $notificationId = Common::getRequestVar('notificationId');
        NotificationManager::cancel($notificationId);

        return json_encode(true);
    }

    protected function getDefaultIndexView()
    {
        if (SettingsPiwik::isInternetEnabled() && Marketplace::isMarketplaceEnabled()) {
            $this->securityPolicy->addPolicy('img-src', '*.matomo.org');
            $this->securityPolicy->addPolicy('default-src', '*.matomo.org');
        }

        $view = new View('@CoreHome/getDefaultIndexView');
        $this->setGeneralVariablesView($view);
        $view->showMenu = true;
        $view->content = '';
        $view->exampleFeatureEnabled = $this->featureFlagManager->isFeatureActive(Example::class);
        $view->groupsWithoutTrackingRequirement = CategoryList::get()->getGroupsWithoutTrackingRequirement();
        return $view;
    }

    protected function setDateTodayIfWebsiteCreatedToday()
    {
        $date = Common::getRequestVar('date', false);
        if (
            $date == 'today'
            || Common::getRequestVar('period', false) == 'range'
        ) {
            return;
        }

        if ($this->site) {
            $datetimeCreationDate      = $this->site->getCreationDate()->getDatetime();
            $creationDateLocalTimezone = Date::factory($datetimeCreationDate, $this->site->getTimezone())->toString('Y-m-d');
            $todayLocalTimezone        = Date::factory('now', $this->site->getTimezone())->toString('Y-m-d');

            if ($creationDateLocalTimezone == $todayLocalTimezone) {
                Matomo::redirectToModule(
                    'CoreHome',
                    'index',
                    array('date'   => 'today',
                          'idSite' => $this->idSite,
                          'period' => Common::getRequestVar('period'))
                );
            }
        }
    }

    public function index()
    {
        $this->setDateTodayIfWebsiteCreatedToday();
        $view = $this->getDefaultIndexView();
        return $view->render();
    }

    //  --------------------------------------------------------
    //  ROW EVOLUTION
    //  The following methods render the popover that shows the
    //  evolution of a single or multiple rows in a data table
    //  --------------------------------------------------------

    /** Render the entire row evolution popover for a single row */
    public function getRowEvolutionPopover()
    {
        $rowEvolution = $this->makeRowEvolution($isMulti = false);
        $view = new View('@CoreHome/getRowEvolutionPopover');
        return $rowEvolution->renderPopover($this, $view);
    }

    /** Render the entire row evolution popover for multiple rows */
    public function getMultiRowEvolutionPopover()
    {
        $rowEvolution = $this->makeRowEvolution($isMulti = true);
        $view = new View('@CoreHome/getMultiRowEvolutionPopover');
        return $rowEvolution->renderPopover($this, $view);
    }

    /** Generic method to get an evolution graph or a sparkline for the row evolution popover */
    public function getRowEvolutionGraph($fetch = false, $rowEvolution = null)
    {
        if (empty($rowEvolution)) {
            $label = Common::getRequestVar('label', '', 'string');
            $isMultiRowEvolution = strpos($label, ',') !== false;

            $rowEvolution = $this->makeRowEvolution($isMultiRowEvolution, $graphType = 'graphEvolution');
            $rowEvolution->useAvailableMetrics();
        }

        $view = $rowEvolution->getRowEvolutionGraph();
        return $this->renderView($view);
    }

    /** Utility function. Creates a RowEvolution instance. */
    private function makeRowEvolution($isMultiRowEvolution, $graphType = null)
    {
        if ($isMultiRowEvolution) {
            return new MultiRowEvolution($this->idSite, $this->date, $graphType);
        } else {
            return new RowEvolution($this->idSite, $this->date, $graphType);
        }
    }

    /**
     * Forces a check for updates and re-renders the header message.
     *
     * This will check piwik.org at most once per 10s.
     */
    public function checkForUpdates()
    {
        Matomo::checkUserHasSomeAdminAccess();
        $this->checkTokenInUrl();

        // perform check (but only once every 10s)
        UpdateCheck::check($force = false, UpdateCheck::UI_CLICK_CHECK_INTERVAL);

        $view = new View('@CoreHome/checkForUpdates');
        $view->isManualUpdateCheck = true;
        $view->lastUpdateCheckFailed = UpdateCheck::hasLastCheckFailed();
        $this->setGeneralVariablesView($view);
        return $view->render();
    }

    /**
     * Redirects the user to a paypal so they can donate to Piwik.
     */
    public function redirectToPaypal()
    {
        $parameters = Request::getRequestArrayFromString($request = null);
        foreach ($parameters as $name => $param) {
            if (
                $name == 'idSite'
                || $name == 'module'
                || $name == 'action'
            ) {
                unset($parameters[$name]);
            }
        }
        $paypalParameters = [
            "cmd" => "_s-xclick",
        ];
        if (empty($parameters["onetime"]) || $parameters["onetime"] != "true") {
            $paypalParameters["hosted_button_id"] = "DVKLY73RS7JTE";
            $paypalParameters["currency_code"] = "USD";
            $paypalParameters["on0"] = "Piwik Supporter";
            if (!empty($parameters["os0"])) {
                $paypalParameters["os0"] = $parameters["os0"];
            }
        } else {
            $paypalParameters["hosted_button_id"] = "RPL23NJURMTFA";
        }

        $url = "https://www.paypal.com/cgi-bin/webscr?" . Url::getQueryStringFromParameters($paypalParameters);

        Url::redirectToUrl($url);
        exit;
    }

    public function saveViewDataTableParameters()
    {
        Matomo::checkUserIsNotAnonymous();
        $this->checkTokenInUrl();

        $reportId   = Common::getRequestVar('report_id', null, 'string');
        $parameters = (array) Common::getRequestVar('parameters', null, 'json');
        $login      = Matomo::getCurrentUserLogin();
        $containerId = Common::getRequestVar('containerId', '', 'string');

        ViewDataTableManager::saveViewDataTableParameters($login, $reportId, $parameters, $containerId);
    }
}
