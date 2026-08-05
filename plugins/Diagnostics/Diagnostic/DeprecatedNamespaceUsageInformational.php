<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\Diagnostic;

use Matomo\Plugin\Manager;
use Matomo\Translation\Translator;

/**
 * Informational diagnostic that surfaces deprecated `Piwik\` namespace usage in
 * each installed plugin's source, so administrators and plugin authors can see
 * which plugins still need migrating before the `Piwik\` alias layer is removed
 * in Matomo 7.0.
 *
 * Eager transparent aliasing (see {@see \LegacyAutoloader}) means the alias
 * autoloader never fires for `Piwik\` lookups, so deprecation cannot be detected
 * at runtime. Instead this diagnostic runs a STATIC source scan: it walks each
 * installed plugin's PHP source for `Piwik\<Class>` namespace references and
 * emits one informational result per plugin that has any, naming the plugin and
 * listing the offending files. When no plugin has deprecated usage, a single
 * clean informational result is emitted instead. Matomo core itself is kept clean
 * by a separate source-guard.
 *
 * @internal Back-compat machinery for the `Piwik\` -> `Matomo\` namespace
 *           migration; removed together with the alias layer in Matomo 7.0.
 */
class DeprecatedNamespaceUsageInformational implements \Matomo\Plugins\Diagnostics\Diagnostic\Diagnostic
{
    /**
     * Maximum number of file paths to list in a single diagnostic result's
     * comment before summarising the rest as a count.
     */
    private const MAX_FILES_TO_LIST = 5;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * Per-plugin deprecated usage to report. When `null`, the diagnostic scans
     * installed plugin source live at execution time via
     * {@see scanInstalledPlugins()}. Injecting usage directly is a test seam.
     *
     * Shape: `[pluginName => list<string>]` where each string is a file path
     * relative to the plugin directory (forward slashes) that contains a
     * deprecated `Piwik\` namespace reference.
     *
     * @var array<string, array<int, string>>|null
     */
    private $usage;

    public function __construct(Translator $translator, ?array $usage = null)
    {
        $this->translator = $translator;
        $this->usage = $usage;
    }

    public function execute()
    {
        $usage = $this->usage ?? self::scanInstalledPlugins();

        if (empty($usage)) {
            return [
                \Matomo\Plugins\Diagnostics\Diagnostic\DiagnosticResult::informationalResult(
                    $this->translator->translate('Diagnostics_DeprecatedNamespaceUsage'),
                    $this->translator->translate('Diagnostics_DeprecatedNamespaceUsageNone')
                ),
            ];
        }

        $results = [];

        // Sort by plugin name for stable, deterministic output.
        $plugins = array_keys($usage);
        sort($plugins);

        foreach ($plugins as $pluginName) {
            $files = $usage[$pluginName];
            if (empty($files)) {
                continue;
            }

            $results[] = DiagnosticResult::informationalResult(
                $this->translator->translate('Diagnostics_DeprecatedNamespaceUsageForPlugin', [$pluginName]),
                $this->formatFiles($files)
            );
        }

        return $results;
    }

    /**
     * Scans every installed plugin's source for deprecated `Piwik\` namespace
     * references and returns the per-plugin file lists. The diagnostic's own
     * scanner file is skipped so it does not flag itself.
     *
     * @return array<string, array<int, string>> Shape: `[plugin => [file, ...]]`.
     */
    public static function scanInstalledPlugins(): array
    {
        $selfFile = (new \ReflectionClass(self::class))->getFileName();
        $usage = [];

        foreach (Manager::getInstance()->getInstalledPluginsName() as $pluginName) {
            $pluginDir = Manager::getPluginDirectory($pluginName);
            if (!is_dir($pluginDir)) {
                continue;
            }

            $files = self::scanPluginDirectory($pluginDir);
            $files = array_values(array_filter($files, function ($relativePath) use ($pluginDir, $selfFile) {
                return rtrim($pluginDir, '/') . '/' . $relativePath !== str_replace('\\', '/', $selfFile);
            }));

            if (!empty($files)) {
                $usage[$pluginName] = $files;
            }
        }

        return $usage;
    }

    /**
     * Recursively scans a plugin directory for PHP files containing a deprecated
     * `Piwik\<Class>` namespace reference (a `Piwik\` followed by an uppercase
     * letter, so it matches `namespace Matomo\Foo`, `use Matomo\Foo`, `Matomo\Foo`
     * FQCNs, and `'Matomo\Foo'` strings, but not this scanner's own `Piwik\`
     * search needle). Test and vendor trees are excluded so development fixtures
     * do not produce noise.
     *
     * @param string $directory Absolute path to a plugin directory.
     * @return list<string> File paths relative to $directory (forward slashes),
     *                      sorted, for files that contain a deprecated reference.
     */
    public static function scanPluginDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $excludeDirs = ['tests', 'Tests', 'test', 'vendor', 'node_modules', 'javascripts', 'lang'];
        $pattern = '{Piwik\\\\[A-Z]}';

        $files = [];
        /** @var \SplFileInfo $fileInfo */
        foreach (self::recursivePhpFiles($directory, $excludeDirs) as $fileInfo) {
            $contents = @file_get_contents($fileInfo->getPathname());
            if ($contents === false) {
                continue;
            }
            if (preg_match($pattern, $contents)) {
                $relative = ltrim(substr($fileInfo->getPathname(), strlen($directory)), DIRECTORY_SEPARATOR);
                $files[] = str_replace('\\', '/', $relative);
            }
        }

        sort($files);
        return array_values(array_unique($files));
    }

    /**
     * @param array<int, string> $excludeDirNames Basenames of directories to skip.
     * @return \Generator<int, \SplFileInfo>
     */
    private static function recursivePhpFiles(string $directory, array $excludeDirNames)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                function (\SplFileInfo $current, $key, \RecursiveIteratorIterator $iterator) use ($excludeDirNames) {
                    if ($current->isDir()) {
                        return !in_array($current->getFilename(), $excludeDirNames, true);
                    }

                    return $current->isFile() && $current->getExtension() === 'php';
                }
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            yield $fileInfo;
        }
    }

    /**
     * @param array<int, string> $files
     * @return string
     */
    private function formatFiles(array $files)
    {
        $count = count($files);
        $shown = array_slice($files, 0, self::MAX_FILES_TO_LIST);
        $comment = $count . ' file' . ($count === 1 ? '' : 's') . ': ' . implode(', ', $shown);
        $remaining = $count - count($shown);
        if ($remaining > 0) {
            $comment .= ', ... and ' . $remaining . ' more';
        }

        return $comment;
    }
}
