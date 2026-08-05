<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CoreUpdater;

use Exception;
use Matomo\API\ResponseBuilder;
use Matomo\Common;
use Matomo\Filesystem;
use Matomo\FrontController;
use Matomo\Matomo;
use Matomo\SettingsPiwik;
use Matomo\UpdateCheck;
use Matomo\Updater as PiwikCoreUpdater;
use Matomo\Version;

class CoreUpdater extends \Matomo\Plugin
{
    /**
     * @see \Matomo\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Request.dispatchCoreAndPluginUpdatesScreen' => 'dispatch',
            'Platform.initialized'                       => 'updateCheck',
        );
    }

    public function dispatch()
    {
        if (!SettingsPiwik::isAutoUpdateEnabled()) {
            return;
        }

        $module = Matomo::getModule();
        $action = Matomo::getAction();

        if (
            $module == 'CoreUpdater'
            // Proxy module is used to redirect users to piwik.org, should still work when Piwik must be updated
            || $module == 'Proxy'
            // Do not show update page during installation.
            || $module == 'Installation'
            || ($module == 'CorePluginsAdmin' && $action == 'deactivate')
            || ($module == 'CorePluginsAdmin' && $action == 'uninstall')
            || ($module == 'LanguagesManager' && $action == 'saveLanguage')
        ) {
            return;
        }

        $updater = new PiwikCoreUpdater();
        $updates = $updater->getComponentsWithNewVersion(array('core' => Version::VERSION));

        if (!empty($updates)) {
            Filesystem::deleteAllCacheOnUpdate();
        }

        if ($updater->getComponentUpdates() !== null) {
            if (FrontController::shouldRethrowException()) {
                throw new Exception("Piwik and/or some plugins have been upgraded to a new version. \n" .
                    "--> Please run the update process first. See documentation: https://matomo.org/docs/update/ \n");
            } elseif ($module === 'API' && ('' == $action || 'index' == $action)) {
                $outputFormat = strtolower(Common::getRequestVar('format', 'xml', 'string', $_GET + $_POST));
                $response = new ResponseBuilder($outputFormat);
                $e = new Exception('Database Upgrade Required. Your Matomo database is out-of-date, and must be upgraded before you can continue.');
                echo $response->getResponseException($e);
                Common::sendResponseCode(503);
                exit;
            } else {
                Matomo::redirectToModule('CoreUpdater');
            }
        }
    }

    public function updateCheck()
    {
        UpdateCheck::check();
    }
}
