<?php

/**
 * Loads the facade class file.
 *
 * @see core/Piwik.php
 */
function loadFacade()
{
    require_once PIWIK_INCLUDE_PATH . "/core/Piwik.php";
    // The lowercase shim file still exists and must not be rewritten.
    include PIWIK_DOCUMENT_ROOT . '/piwik.php';
}
-----
<?php

/**
 * Loads the facade class file.
 *
 * @see core/Matomo.php
 */
function loadFacade()
{
    require_once PIWIK_INCLUDE_PATH . "/core/Matomo.php";
    // The lowercase shim file still exists and must not be rewritten.
    include PIWIK_DOCUMENT_ROOT . '/piwik.php';
}
