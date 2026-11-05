<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Error;

/**
 * Walks files/directories, migrates every PHP file (and, when text extensions
 * are configured, every file with one of those extensions via the shared
 * value-aware swap rule), and writes only files whose content actually
 * changed. Parse failures are recorded per file and never abort the run;
 * text files cannot fail (no parsing).
 */
final class MigrationRunner
{
    private const SKIPPED_DIR_PATTERN = '/(^|[\/\\\\])('
        . '(?:vendor|\.git|node_modules)'
        . ')([\/\\\\]|$)/';

    private Migrator $migrator;

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    /**
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function __construct(?Migrator $migrator = null, $stdout = null, $stderr = null)
    {
        $this->migrator = $migrator ?? new Migrator();
        $this->stdout = $stdout ?? STDOUT;
        $this->stderr = $stderr ?? STDERR;
    }

    /**
     * Migrates all PHP files (and configured text files) under the given paths.
     *
     * @param list<string> $paths
     * @param list<string> $allowedPaths File/directory prefixes never rewritten
     *                                   (same semantics as the source-clean guard).
     * @param list<string> $textExtensions File extensions (lowercase, no dot) whose
     *                                     content is swapped via the shared rule.
     * @return int 0 when every file was processed, 1 when any file failed.
     */
    public function run(array $paths, bool $dryRun = false, array $allowedPaths = [], array $textExtensions = []): int
    {
        $changed = 0;
        $unchanged = 0;
        $failed = [];
        $extensions = array_merge(['php'], $textExtensions);

        foreach (self::collectFiles($paths, $failed, $extensions) as $path) {
            if ($allowedPaths !== [] && SourceCleanGuard::isAllowed($path, $allowedPaths)) {
                $unchanged++;

                continue;
            }

            $original = @file_get_contents($path);

            if ($original === false) {
                $failed[] = $path . ': unreadable file';

                continue;
            }

            if (self::isPhpPath($path)) {
                try {
                    $migrated = $this->migrator->migrate($original);
                } catch (Error $error) {
                    $failed[] = $path . ': ' . $error->getMessage();

                    continue;
                } catch (\Throwable $throwable) {
                    $failed[] = $path . ': ' . $throwable->getMessage();

                    continue;
                }
            } else {
                // Text file: pure byte rule, no parsing, no format-preserving print.
                $migrated = RootNamespace::swapQualified($original);
            }

            if ($migrated === $original) {
                $unchanged++;

                continue;
            }

            if ($dryRun) {
                $this->writeln($this->stdout, 'would change: ' . $path);
                $changed++;

                continue;
            }

            if (@file_put_contents($path, $migrated) === false) {
                $failed[] = $path . ': failed to write';

                continue;
            }

            $this->writeln($this->stdout, 'changed: ' . $path);
            $changed++;
        }

        foreach ($failed as $message) {
            $this->writeln($this->stderr, 'ERROR: ' . $message);
        }

        $verb = $dryRun ? 'would change' : 'changed';
        $this->writeln($this->stdout, sprintf('%d %s, %d unchanged, %d failed', $changed, $verb, $unchanged, count($failed)));

        return $failed === [] ? 0 : 1;
    }

    /**
     * Collects every PHP file under the given paths, recursively, applying the
     * same skip rules as run() (vendor/.git/node_modules and git-submodule
     * checkouts are never touched).
     *
     * @param list<string> $paths
     * @param list<string> $failed Filled with 'path: reason' entries for unreadable paths.
     * @return list<string>
     */
    public static function collectPhpFiles(array $paths, array &$failed): array
    {
        return self::collectFiles($paths, $failed);
    }

    /**
     * Collects every file under the given paths whose extension is in
     * $extensions, recursively, applying the same skip rules as run()
     * (vendor/.git/node_modules and git-submodule checkouts are never
     * touched). Explicit file arguments with an extension outside the set are
     * skipped (not reported as failed); extensionless explicit files (e.g. the
     * `console` entrypoint) are kept as PHP.
     *
     * @param list<string> $paths
     * @param list<string> $failed Filled with 'path: reason' entries for unreadable paths.
     * @param list<string> $extensions File extensions (lowercase, no dot) to collect.
     * @return list<string>
     */
    public static function collectFiles(array $paths, array &$failed, array $extensions = ['php']): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                if ($extension !== '' && !in_array($extension, $extensions, true)) {
                    continue;
                }

                $files[] = $path;

                continue;
            }

            if (is_dir($path)) {
                foreach (self::collectDir($path, $failed, $extensions) as $file) {
                    $files[] = $file;
                }

                continue;
            }

            $failed[] = $path . ': no such file or directory';
        }

        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param list<string> $failed Filled with 'path: reason' entries for unreadable directories.
     * @param list<string> $extensions File extensions (lowercase, no dot) to collect.
     * @return list<string>
     */
    private static function collectDir(string $dir, array &$failed, array $extensions): array
    {
        $files = [];

        try {
            $directoryIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

            $filteredIterator = new \RecursiveCallbackFilterIterator(
                $directoryIterator,
                function (\SplFileInfo $fileInfo): bool {
                    if (!$fileInfo->isDir()) {
                        return true;
                    }

                    $path = $fileInfo->getPathname();
                    $name = $fileInfo->getFilename();

                    // Never descend into VCS or vendor directories.
                    if ($name === '.git' || $name === 'vendor' || $name === 'node_modules') {
                        return false;
                    }

                    // Git submodule checkouts are separate repositories; in a checkout their
                    // .git is a file (gitlink marker), not a directory. Never touch them.
                    if (is_file($path . DIRECTORY_SEPARATOR . '.git')) {
                        return false;
                    }

                    return true;
                }
            );

            $iterator = new \RecursiveIteratorIterator($filteredIterator, \RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || !in_array($fileInfo->getExtension(), $extensions, true)) {
                    continue;
                }

                $path = $fileInfo->getPathname();

                if (preg_match(self::SKIPPED_DIR_PATTERN, $path)) {
                    continue;
                }

                $files[] = $path;
            }
        } catch (\UnexpectedValueException $exception) {
            $failed[] = $dir . ': ' . $exception->getMessage();
        }

        return $files;
    }

    /**
     * True for PHP files: .php files and extensionless files (the `console`
     * entrypoint is an extensionless PHP script and is part of the canonical
     * path set).
     */
    public static function isPhpPath(string $path): bool
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === 'php' || $extension === '';
    }

    /**
     * @param resource $stream
     */
    private function writeln($stream, string $line): void
    {
        fwrite($stream, $line . PHP_EOL);
    }
}
