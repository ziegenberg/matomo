<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\Diagnostic;

/**
 * Default provider: the deprecated `Piwik\` usage recorded in-process by the legacy
 * autoloader during the current request.
 *
 * The System Check request loads every activated plugin, so each un-migrated plugin's
 * `extends \Piwik\Plugin` (and similar declarations) fires a deprecation attributed to
 * it. Reading the current request's recording therefore names the activated plugins that
 * still reference the deprecated namespace, with no cross-request persistence or database
 * write on the deprecation hot path.
 */
class RecordedDeprecatedNamespaceUsageProvider implements DeprecatedNamespaceUsageProvider
{
    public function getRecordedUsages(): array
    {
        return \LegacyAutoloader::getRecordedDeprecations();
    }
}
