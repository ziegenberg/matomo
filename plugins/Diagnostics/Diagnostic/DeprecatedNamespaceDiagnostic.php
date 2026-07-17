<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Common;

/**
 * Informational System Report diagnostic listing the plugins that still reference the
 * deprecated `Piwik\` namespace.
 *
 * Fed by the deprecation logger's recorded usage (see {@link DeprecatedNamespaceUsageProvider}),
 * which attributes each deprecated `Piwik\` resolution to the calling plugin. A
 * deprecation attributed to core (`plugin` null) is not a plugin and is skipped here; core
 * cleanliness is enforced separately by the source-clean guard.
 *
 * No new UI: the result flows through the existing System Check as an informational
 * diagnostic.
 */
class DeprecatedNamespaceDiagnostic implements Diagnostic
{
    public const LABEL = 'Deprecated Piwik\ namespace usage';

    private const CLEAN_MESSAGE = 'No deprecated Piwik\ namespace usage observed.';

    /**
     * @var DeprecatedNamespaceUsageProvider
     */
    private $provider;

    public function __construct(DeprecatedNamespaceUsageProvider $provider)
    {
        $this->provider = $provider;
    }

    public function execute()
    {
        $byPlugin = $this->groupUsagesByPlugin($this->provider->getRecordedUsages());

        $result = new DiagnosticResult(self::LABEL);

        if (empty($byPlugin)) {
            $result->addItem(new DiagnosticResultItem(
                DiagnosticResult::STATUS_INFORMATIONAL,
                self::CLEAN_MESSAGE
            ));
            return [$result];
        }

        ksort($byPlugin);
        foreach ($byPlugin as $plugin => $classes) {
            sort($classes);
            $comment = sprintf(
                '%s uses deprecated Piwik\ namespace: %s',
                $plugin,
                implode(', ', $classes)
            );
            $result->addItem(new DiagnosticResultItem(
                DiagnosticResult::STATUS_INFORMATIONAL,
                Common::sanitizeInputValue($comment)
            ));
        }

        return [$result];
    }

    /**
     * Groups recorded deprecations by attributing plugin, skipping core-originated usage
     * (`plugin` null) and collapsing duplicate classes per plugin.
     *
     * @param array<int, array{piwik: string, matomo: string, plugin: string|null}> $usages
     * @return array<string, array<int, string>> plugin name => distinct Piwik\ classes
     */
    private function groupUsagesByPlugin(array $usages): array
    {
        $byPlugin = [];
        foreach ($usages as $usage) {
            $plugin = $usage['plugin'];
            if ($plugin === null) {
                continue;
            }
            if (!isset($byPlugin[$plugin])) {
                $byPlugin[$plugin] = [];
            }
            $class = $usage['piwik'];
            if (!in_array($class, $byPlugin[$plugin], true)) {
                $byPlugin[$plugin][] = $class;
            }
        }
        return $byPlugin;
    }
}
