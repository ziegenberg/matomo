<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Error;

/**
 * Walks files/directories, migrates every PHP file, and writes only files
 * whose content actually changed. Parse failures are recorded per file and
 * never abort the run.
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
     * Migrates all PHP files under the given paths.
     *
     * @param list<string> $paths
     * @return int 0 when every file was processed, 1 when any file failed.
     */
    public function run(array $paths, bool $dryRun = false): int
    {
        $changed = 0;
        $unchanged = 0;
        $failed = [];

        foreach (self::collectPhpFiles($paths, $failed) as $path) {
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
        $files = [];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;

                continue;
            }

            if (is_dir($path)) {
                foreach (self::collectDir($path, $failed) as $file) {
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
     * @return list<string>
     */
    private static function collectDir(string $dir, array &$failed): array
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
                if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
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
     * @param resource $stream
     */
    private function writeln($stream, string $line): void
    {
        fwrite($stream, $line . PHP_EOL);
    }
}
