<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

use Matomo\ErrorHandler;
use Matomo\ExceptionHandler;
use Matomo\FrontController;

if (!defined('PIWIK_ENABLE_ERROR_HANDLER') || PIWIK_ENABLE_ERROR_HANDLER) {
    ErrorHandler::registerErrorHandler();
    ExceptionHandler::setUp();
}

FrontController::setUpSafeMode();

if (!defined('PIWIK_ENABLE_DISPATCH')) {
    define('PIWIK_ENABLE_DISPATCH', true);
}

if (PIWIK_ENABLE_DISPATCH) {
    $environment = new \Matomo\Application\Environment(null);
    $environment->init();

    $controller = FrontController::getInstance();

    try {
        $controller->init();
        $response = $controller->dispatch();

        if (!is_null($response)) {
            echo $response;
        }
    } catch (Exception $ex) {
        ExceptionHandler::dieWithHtmlErrorPage($ex);
    }
}
