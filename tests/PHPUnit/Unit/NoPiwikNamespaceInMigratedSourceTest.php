<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

namespace Matomo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Regression guard for the `Piwik\` -> `Matomo\` root-namespace migration.
 *
 * Migrated source (core/ and the in-repo bundled plugins) must not reintroduce
 * load-bearing `Piwik\` namespace references in CODE: through 6.x such refs
 * resolve only via the `LegacyAutoloader` alias layer, which is removed in 7.0.
 * This test tokenizes every PHP file under core/ and plugins/ (excluding the
 * still-`Piwik\` submodule plugins, test trees, and vendor) and fails if any
 * non-comment token references `Piwik\<Class>` outside a small, explicit
 * allowlist of intentional back-compat sites.
 *
 * Comment/PHPDoc `Piwik\` references (docblock code samples, `{@link}` prose)
 * are NOT scanned: they are documentation text, not load-bearing class refs,
 * and are cleaned separately as a docs-quality concern.
 *
 * @group Core
 * @group NoPiwikNamespaceInMigratedSource
 */
class NoPiwikNamespaceInMigratedSourceTest extends TestCase
{
    /**
     * Submodule plugins (managed in their own repos). They migrate per-repo
     * and are out of scope.
     */
    private const SUBMODULE_PLUGINS = [
        'AnonymousPiwikUsageMeasurement', 'Bandwidth', 'CustomAlerts', 'CustomVariables',
        'DeviceDetectorCache', 'LogViewer', 'LoginLdap', 'MarketingCampaignsReporting',
        'Provider', 'QueuedTracking', 'SecurityInfo', 'TagManager', 'TasksTimetable',
        'TrackingSpamPrevention', 'TreemapVisualization', 'VisitorGenerator',
    ];

    /**
     * Intentional `Piwik\` CODE references that must survive through 6.x.
     *
     * Map of `relative-path (from repo root) => expected count of CODE tokens
     * referencing Piwik\`. The guard fails if a file's actual count differs, so
     * adding or removing an intentional reference forces an explicit allowlist
     * update rather than silently passing.
     *
     * - core/Plugin/Manager.php: dual-root autoload keeps the `Piwik\Plugins\`
     *   PSR-4 prefix (and a legacy `Piwik\Plugins\` class-name fallback) so
     *   un-migrated plugins keep loading natively.
     * - core/DataTable.php: serialized-archive back-compat class-name strings
     *   that must match data serialized under the old namespace.
     * - plugins/Diagnostics/.../DeprecatedNamespaceUsageInformational.php: the
     *   diagnostic's own detection regex literally matches `Piwik\<Class>`.
     * - plugins/ExamplePlugin/AutoloadFixture/PiwikNamespacedClass.php: fixture
     *   that deliberately stays `Piwik\` to exercise the alias layer.
     */
    private const ALLOWLIST = [
        'core/Plugin/Manager.php' => 1,
        'core/DataTable.php' => 2,
        'plugins/Diagnostics/Diagnostic/DeprecatedNamespaceUsageInformational.php' => 1,
        'plugins/ExamplePlugin/AutoloadFixture/PiwikNamespacedClass.php' => 1,
    ];

    public function testMigratedSourceHasNoPiwikNamespaceReferencesInCode(): void
    {
        $root = defined('PIWIK_INCLUDE_PATH') ? PIWIK_INCLUDE_PATH : dirname(__DIR__, 3);
        $refsByFile = $this->scanForCodePiwikRefs($root);

        $failures = [];
        foreach ($refsByFile as $relativePath => $count) {
            $expected = self::ALLOWLIST[$relativePath] ?? 0;
            if ($count !== $expected) {
                $failures[] = "$relativePath has $count `Piwik\\` CODE reference(s); expected $expected."
                    . ' If this is a new intentional back-compat site, add it to the allowlist in '
                    . __CLASS__ . '. Otherwise migrate the reference to `Matomo\\`.';
            }
        }

        // Detect stale allowlist entries (a file whose intentional refs were
        // removed) so the allowlist cannot silently rot.
        foreach (self::ALLOWLIST as $allowedFile => $expectedCount) {
            if (!isset($refsByFile[$allowedFile])) {
                $failures[] = "$allowedFile is allowlisted with $expectedCount ref(s) but has none;"
                    . ' remove it from the allowlist.';
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    /**
     * Tokenize every in-scope PHP file and count non-comment tokens that contain
     * a `Piwik\` namespace reference (`Piwik\` followed by a class-name char).
     *
     * @param string $root Repo root (PIWIK_INCLUDE_PATH).
     * @return array<string,int> Map of `relative-path => count`.
     */
    private function scanForCodePiwikRefs(string $root): array
    {
        $refs = [];
        foreach (['core', 'plugins'] as $dir) {
            $base = $root . '/' . $dir;
            if (!is_dir($base)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $path = $file->getPathname();
                // Skip vendor and test trees.
                if (
                    strpos($path, '/vendor/') !== false
                    || strpos($path, '/node_modules/') !== false
                    || strpos($path, '/tests/') !== false
                ) {
                    continue;
                }
                // Skip still-`Piwik\` submodule plugins.
                foreach (self::SUBMODULE_PLUGINS as $plugin) {
                    if (strpos($path, '/plugins/' . $plugin . '/') !== false) {
                        continue 2;
                    }
                }
                $count = $this->countCodePiwikRefs($path);
                if ($count > 0) {
                    $refs[substr($path, strlen($root) + 1)] = $count;
                }
            }
        }
        return $refs;
    }

    /**
     * Count `Piwik\<Class>` references in CODE (non-comment) tokens of a file.
     */
    private function countCodePiwikRefs(string $path): int
    {
        $source = file_get_contents($path);
        if ($source === false || strpos($source, 'Piwik\\') === false) {
            return 0;
        }
        $count = 0;
        foreach (token_get_all($source) as $token) {
            if (!is_array($token)) {
                continue;
            }
            $name = token_name($token[0]);
            if ($name === 'T_COMMENT' || $name === 'T_DOC_COMMENT') {
                continue;
            }
            // `Piwik` + one-or-more backslashes + a class-name char (or `[` for
            // regex patterns). Matches single-backslash FQCNs and escaped forms
            // in single-quoted strings alike.
            if (preg_match('/Piwik\\\\+(?:[A-Za-z_]|\[)/', $token[1])) {
                $count++;
            }
        }
        return $count;
    }
}
