<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Filesystem;
use Piwik\Plugin\Manager;

/**
 * Reports installed plugins that still use the deprecated Piwik\ namespace in their PHP sources.
 */
class DeprecatedNamespaceUsageInformational implements Diagnostic
{
    public const MAX_LISTED_FILES = 5;

    public const EXCLUDED_PATH_SEGMENTS = ['/tests/', '/vendor/', '/javascripts/', '/lang/'];

    private const PIWIK_NAMESPACE_PATTERN = '/\bPiwik\\\\[A-Z]\w*/';

    /**
     * Map of plugin name to absolute file paths, or null to scan installed plugins.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $pluginFiles;

    /**
     * @param array<string, list<string>>|null $pluginFiles Map of plugin name to absolute file paths.
     *                                                       Passed in tests; null triggers a scan of installed plugins.
     */
    public function __construct(?array $pluginFiles = null)
    {
        $this->pluginFiles = $pluginFiles;
    }

    public function execute()
    {
        $filesByPlugin = $this->pluginFiles ?? $this->scanInstalledPlugins();

        $offendingFilesByPlugin = [];
        foreach ($filesByPlugin as $pluginName => $files) {
            $offendingFiles = $this->scanPluginDirectory($pluginName, $files);
            if (!empty($offendingFiles)) {
                $offendingFilesByPlugin[$pluginName] = $offendingFiles;
            }
        }

        if (empty($offendingFilesByPlugin)) {
            return [
                DiagnosticResult::informationalResult(
                    'Deprecated Piwik namespace usage',
                    'No deprecated Piwik\ namespace usage found in installed plugins.'
                ),
            ];
        }

        $results = [];
        foreach ($offendingFilesByPlugin as $pluginName => $files) {
            $comment = implode(', ', array_slice($files, 0, self::MAX_LISTED_FILES));
            if (count($files) > self::MAX_LISTED_FILES) {
                $comment .= ' (and ' . (count($files) - self::MAX_LISTED_FILES) . ' more)';
            }

            $results[] = DiagnosticResult::informationalResult(
                'Deprecated Piwik namespace usage in ' . $pluginName,
                $comment
            );
        }

        return $results;
    }

    /**
     * @param list<string> $files Absolute file paths to scan.
     * @return list<string> Files containing a deprecated Piwik\ namespace reference.
     */
    public function scanPluginDirectory(string $pluginName, array $files): array
    {
        $pluginDirectory = Manager::getPluginDirectory($pluginName);
        $offendingFiles = [];

        foreach ($files as $file) {
            if ($this->isExcludedPath($file)) {
                continue;
            }

            $contents = @file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            if (!preg_match(self::PIWIK_NAMESPACE_PATTERN, $contents)) {
                continue;
            }

            if (str_starts_with($file, $pluginDirectory . '/')) {
                $file = substr($file, strlen($pluginDirectory) + 1);
            }

            $offendingFiles[] = $file;
        }

        return $offendingFiles;
    }

    /**
     * @return array<string, list<string>> Map of installed plugin name to absolute PHP file paths.
     */
    public function scanInstalledPlugins(): array
    {
        $filesByPlugin = [];

        foreach (Manager::getInstance()->getInstalledPluginsName() as $pluginName) {
            $pluginDirectory = Manager::getPluginDirectory($pluginName);
            if (!is_dir($pluginDirectory)) {
                continue;
            }

            $filesByPlugin[$pluginName] = Filesystem::globr($pluginDirectory, '*.php');
        }

        return $filesByPlugin;
    }

    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PATH_SEGMENTS as $segment) {
            if (str_contains($path, $segment)) {
                return true;
            }
        }

        return false;
    }
}
