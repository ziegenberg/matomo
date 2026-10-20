<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Error;

/**
 * Source-clean guard: scans PHP files for leftover Piwik\ root-namespace
 * references that the migration would rewrite.
 *
 * The check is idempotency-based: a file is a violation exactly when running
 * the migrator over it changes it. This flags precisely what the swapper
 * would rewrite (code names, string literals, and docblock types), while
 * plain comments, display strings, PIWIK_* names, and alias-guarded
 * references (e.g. `use Matomo\Cache as Piwik;` plus `Piwik::` calls) stay
 * clean by construction.
 */
final class SourceCleanGuard
{
    private Migrator $migrator;

    public function __construct(?Migrator $migrator = null)
    {
        $this->migrator = $migrator ?? new Migrator();
    }

    /**
     * Scans the given paths for files the migration would rewrite.
     *
     * @param list<string> $paths Files or directories to scan.
     * @param list<string> $allowedPaths File/directory prefixes exempt from the check
     *                                   (absolute or repo-relative).
     * @return array{violations: list<string>, failed: list<string>} Violations
     *               are paths that would change; failed are paths that could not
     *               be read or parsed.
     */
    public function scan(array $paths, array $allowedPaths = []): array
    {
        $violations = [];
        $failed = [];

        foreach (MigrationRunner::collectPhpFiles($paths, $failed) as $path) {
            if ($this->isAllowed($path, $allowedPaths)) {
                continue;
            }

            $original = @file_get_contents($path);

            if ($original === false) {
                $failed[] = $path . ': unreadable file';

                continue;
            }

            try {
                $migrated = $this->migrator->migrate($original);
            } catch (Error $error) {
                $failed[] = $path . ': ' . $error->getMessage();

                continue;
            } catch (\Throwable $throwable) {
                $failed[] = $path . ': ' . $throwable->getMessage();

                continue;
            }

            if ($migrated !== $original) {
                $violations[] = $path;
            }
        }

        return ['violations' => $violations, 'failed' => $failed];
    }

    /**
     * Repo-relative prefixes of files that legitimately contain Piwik\ text
     * and must stay outside the guard:
     *  - LegacyAutoloader.php and its tests keep the legacy Piwik\ autoload
     *    behavior (LegacyAutoLoaderTest is checked by the migration itself).
     *  - PluginNamespaceAutoloadTest.php (issue 03) covers namespace autoload
     *    fixtures by design.
     *  - plugins/Diagnostics/ hosts the Piwik\ deprecation scanner (issue 04).
     *  - utils/migrate-namespace/ is the tool itself (Piwik\ constants and
     *    fixture strings).
     *
     * @return list<string>
     */
    public static function defaultAllowlist(): array
    {
        return [
            'LegacyAutoloader.php',
            'tests/PHPUnit/Unit/LegacyAutoLoaderTest.php',
            'tests/PHPUnit/Unit/PluginNamespaceAutoloadTest.php',
            'plugins/Diagnostics/',
            'utils/migrate-namespace/',
        ];
    }

    /**
     * @param list<string> $allowedPaths
     */
    private function isAllowed(string $path, array $allowedPaths): bool
    {
        if ($allowedPaths === []) {
            return false;
        }

        $relative = self::repoRelativePath($path);

        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return true;
            }

            if ($relative !== null && str_starts_with($relative, $allowedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The path relative to the repository root, or null when the path is
     * outside the repository.
     */
    private static function repoRelativePath(string $path): ?string
    {
        $root = dirname(__DIR__, 3);

        if (str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            return substr($path, strlen($root) + 1);
        }

        return null;
    }
}
