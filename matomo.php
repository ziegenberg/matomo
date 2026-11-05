<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

use Matomo\SettingsServer;
use Matomo\Tracker\RequestSet;
use Matomo\Tracker;
use Matomo\Tracker\Handler;
use Matomo\API\CORSHandler;

@ignore_user_abort(true);

// Note: if you wish to debug the Tracking API please see this documentation:
// https://developer.matomo.org/api-reference/tracking-api#debugging-the-tracker

if (!defined('PIWIK_DOCUMENT_ROOT')) {
    define('PIWIK_DOCUMENT_ROOT', dirname(__FILE__) == '/' ? '' : dirname(__FILE__));
}
if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}
if (!defined('PIWIK_INCLUDE_PATH')) {
    define('PIWIK_INCLUDE_PATH', PIWIK_DOCUMENT_ROOT);
}

require_once PIWIK_INCLUDE_PATH . '/core/bootstrap.php';

require_once PIWIK_INCLUDE_PATH . '/core/Request/AuthenticationToken.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Controller.php';
require_once PIWIK_INCLUDE_PATH . '/core/Exception/NotYetInstalledException.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/ControllerAdmin.php';
require_once PIWIK_INCLUDE_PATH . '/core/Singleton.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Manager.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin.php';
require_once PIWIK_INCLUDE_PATH . '/core/Common.php';
require_once PIWIK_INCLUDE_PATH . '/core/Matomo.php';
require_once PIWIK_INCLUDE_PATH . '/core/IP.php';
require_once PIWIK_INCLUDE_PATH . '/core/UrlHelper.php';
require_once PIWIK_INCLUDE_PATH . '/core/Url.php';
require_once PIWIK_INCLUDE_PATH . '/core/SettingsPiwik.php';
require_once PIWIK_INCLUDE_PATH . '/core/SettingsServer.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker.php';
require_once PIWIK_INCLUDE_PATH . '/core/Config.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Cache.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Request.php';
require_once PIWIK_INCLUDE_PATH . '/core/Cookie.php';
require_once PIWIK_INCLUDE_PATH . '/core/API/CORSHandler.php';

// The direct requires above declare Matomo\ classes without autoloading, so the
// alias layer's eager catch-up (which runs when the composer autoloader registers,
// before these requires) did not see them. Re-run it so every declared Matomo\
// class gets its Piwik\ counterpart alias immediately: un-migrated plugin type
// checks (e.g. `Piwik\Tracker\Request`) resolve to the same class.
\LegacyAutoloader::catchUpDeclaredClasses();

SettingsServer::setIsTrackerApiRequest();

$environment = new \Matomo\Application\Environment('tracker');
try {
    $environment->init();
} catch(\Matomo\Exception\NotYetInstalledException $e) {
    \Matomo\Common::sendResponseCode(503);
    die($e->getMessage());
}

Tracker::loadTrackerEnvironment();

$corsHandler = new CORSHandler();
$corsHandler->handle();

$tracker    = new Tracker();
$requestSet = new RequestSet();

ob_start();

try {
    $handler  = Handler\Factory::make();
    $response = $tracker->main($handler, $requestSet);

    if (!is_null($response)) {
        echo $response;
    }

} catch (Exception $e) {
    echo "Error:" . $e->getMessage();
    exit(1);
}

if (ob_get_level() > 1) {
    ob_end_flush();
}
