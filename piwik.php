<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

// Back-compat shim: the canonical tracker entrypoint is now matomo.php.
// This file keeps the legacy /piwik.php tracker URL working forever (it is
// part of the public tracker API and embedded in tracker snippets across the
// web) by delegating to matomo.php. Do not add tracker logic here; the
// canonical entrypoint owns it.

if (!defined('PIWIK_DOCUMENT_ROOT')) {
    define('PIWIK_DOCUMENT_ROOT', dirname(__FILE__) == '/' ? '' : dirname(__FILE__));
}

include PIWIK_DOCUMENT_ROOT . '/matomo.php';
