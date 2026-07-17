<?php

namespace Matomo\Test;

/**
 * Test fixture for LegacyAutoLoaderTest attribution coverage.
 *
 * This class lives inside a registered plugin directory (tests/resources/custompluginsdir,
 * wired via $GLOBALS['MATOMO_PLUGIN_DIRS'] in the test bootstrap) so that when it
 * references a deprecated Piwik\ name, the deprecation can be attributed to its
 * originating plugin (CustomDirPlugin) via the backtrace.
 *
 * It deliberately references a Piwik\ vendor class not used elsewhere in the test file so
 * the autoloader fires fresh for it (PHP caches resolved classes per request).
 */
class LegacyAliasCaller
{
    public static function trigger()
    {
        class_exists('Piwik\\Network\\IPv6');
    }
}
