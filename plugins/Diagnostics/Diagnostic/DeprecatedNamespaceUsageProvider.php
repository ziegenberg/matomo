<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\Diagnostic;

/**
 * Source of deprecated `Piwik\` namespace usage recorded by the legacy autoloader, used
 * to feed the System Report diagnostic.
 */
interface DeprecatedNamespaceUsageProvider
{
    /**
     * @return array<int, array{piwik: string, matomo: string, plugin: string|null}>
     */
    public function getRecordedUsages(): array;
}
