<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Installation;

use Matomo\API\Request;
use Matomo\Common;
use Matomo\Config;
use Matomo\Exception\NotYetInstalledException;
use Matomo\FrontController;
use Matomo\Matomo;
use Matomo\Plugins\Installation\Exception\DatabaseConnectionFailedException;
use Matomo\SettingsPiwik;
use Matomo\View as PiwikView;

class Installation extends \Matomo\Plugin
{
    protected $installationControllerName = '\Matomo\Plugins\Installation\Controller';

    /**
     * @see \Matomo\Plugin::registerEvents
     */
    public function registerEvents()
    {
        $hooks = array(
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Config.NoConfigurationFile'      => 'dispatch',
            'Config.badConfigurationFile'     => 'dispatch',
            'Db.cannotConnectToDb'            => 'displayDbConnectionMessage',
            'Request.dispatch'                => 'dispatchIfNotInstalledYet',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
        );
        return $hooks;
    }

    public function getClientSideTranslationKeys(&$translations)
    {
        $translations[] = 'Installation_Legend';
        $translations[] = 'General_Ok';
        $translations[] = 'Installation_SystemCheckWarning';
        $translations[] = 'Installation_SystemCheckError';
        $translations[] = 'General_RefreshPage';
        $translations[] = 'Installation_CopyBelowInfoForSupport';
        $translations[] = 'Installation_CopySystemCheck';
        $translations[] = 'Installation_DownloadSystemCheck';
        $translations[] = 'Installation_Optional';
        $translations[] = 'Installation_InformationalResults';
        $translations[] = 'Installation_SystemCheck';
        $translations[] = 'Installation_Requirements';
        $translations[] = 'Installation_SystemCheckSummaryThereWereErrors';
        $translations[] = 'Installation_SeeBelowForMoreInfo';
        $translations[] = 'Installation_SystemCheckSummaryThereWereWarnings';
        $translations[] = 'Installation_SystemCheckSummaryNoProblems';
    }

    public function displayDbConnectionMessage($exception = null)
    {
        Common::sendResponseCode(500);

        $errorMessage = $exception->getMessage();

        if (Request::isApiRequest(null)) {
            $ex = new DatabaseConnectionFailedException($errorMessage);
            throw $ex;
        }

        $view = new PiwikView("@Installation/cannotConnectToDb");
        $view->exceptionMessage = $errorMessage;

        $ex = new DatabaseConnectionFailedException($view->render());
        $ex->setIsHtmlMessage();

        throw $ex;
    }

    public function dispatchIfNotInstalledYet(&$module, &$action, &$parameters)
    {
        $general = Config::getInstance()->General;

        if (!SettingsPiwik::isMatomoInstalled() && !$general['enable_installer']) {
            throw new NotYetInstalledException('Matomo is not set up yet');
        }

        if (empty($general['installation_in_progress'])) {
            return;
        }

        if ($module == 'Installation') {
            return;
        }

        $module = 'Installation';

        if (!$this->isAllowedAction($action)) {
            $action = 'welcome';
        }

        $parameters = array();
    }

    public function setControllerToLoad($newControllerName)
    {
        $this->installationControllerName = $newControllerName;
    }

    protected function getInstallationController()
    {
        return new $this->installationControllerName();
    }

    /**
     * @param \Exception|null $exception
     */
    public function dispatch($exception = null)
    {
        if ($exception) {
            $message = $exception->getMessage();
        } else {
            $message = '';
        }

        $action = Common::getRequestVar('action', 'welcome', 'string');

        if ($this->isAllowedAction($action) && (!defined('PIWIK_ENABLE_DISPATCH') || PIWIK_ENABLE_DISPATCH)) {
            echo FrontController::getInstance()->dispatch('Installation', $action, array($message));
        } elseif (defined('PIWIK_ENABLE_DISPATCH') && !PIWIK_ENABLE_DISPATCH) {
            if ($exception && $exception instanceof \Exception) {
                throw $exception;
            }
            return;
        } else {
            Matomo::exitWithErrorMessage($this->getMessageToInviteUserToInstallPiwik($message));
        }

        exit;
    }

    /**
     * Adds CSS files to list of CSS files for asset manager.
     */
    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/Installation/stylesheets/systemCheckPage.less";
    }

    private function isAllowedAction($action)
    {
        $controller = $this->getInstallationController();
        $isActionAllowed = in_array($action, array('saveLanguage', 'getInstallationCss', 'getInstallationJs', 'reuseTables'));

        return in_array($action, array_keys($controller->getInstallationSteps()))
                || $isActionAllowed;
    }

    /**
     * @param $message
     * @return string
     */
    private function getMessageToInviteUserToInstallPiwik($message)
    {
        $messageWhenPiwikSeemsNotInstalled =
            $message .
            "\n<br/>" .
            Matomo::translate('Installation_NoConfigFileFound') .
            "<br/><b>» " .
            Matomo::translate('Installation_YouMayInstallPiwikNow', array("<a href='index.php'>", "</a></b>")) .
            "<br/><small>" .
            Matomo::translate('Installation_IfPiwikInstalledBeforeTablesCanBeKept') .
            "</small>";
        return $messageWhenPiwikSeemsNotInstalled;
    }
}
