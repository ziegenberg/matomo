<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

define('PIWIK_PRINT_ERROR_BACKTRACE', true);
define('PIWIK_ENABLE_DISPATCH', false);

require_once __DIR__ . '/../../tests/PHPUnit/proxy/index.php';

$environment = new \Matomo\Application\Environment(null);
$environment->init();

\Matomo\Access::getInstance()->setSuperUserAccess(true);

$executed = false;
\Matomo\Matomo::addAction('Request.dispatch', function () use (&$executed) {
    if (!$executed) {
        $executed = true;
        throw new \Twig\Error\RuntimeError('test message');
    }
});

\Matomo\FrontController::$enableDispatch = true;

\Matomo\FrontController::getInstance()->init();

echo \Matomo\FrontController::getInstance()->dispatch('CoreHome', 'index');
