<?php

declare(strict_types=1);

use Utils\MigrateNamespace\MigrationRunner;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$usage = <<<'USAGE'
Usage: php utils/migrate-namespace/bin/migrate.php [options] <path>...

Rewrites the Piwik\ root namespace to Matomo\ in PHP source files.

Options:
  --dry-run   Report files that would change without writing them.
  --help, -h  Show this help message.

Arguments:
  path        A file or directory (recursively searched for *.php files).
USAGE;

$paths = [];
$dryRun = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--dry-run') {
        $dryRun = true;

        continue;
    }

    if ($argument === '--help' || $argument === '-h') {
        fwrite(STDOUT, $usage . PHP_EOL);
        exit(0);
    }

    if (str_starts_with($argument, '-')) {
        fwrite(STDERR, 'Unknown option: ' . $argument . PHP_EOL);
        fwrite(STDERR, 'Try --help for usage.' . PHP_EOL);
        exit(2);
    }

    $paths[] = $argument;
}

if ($paths === []) {
    fwrite(STDERR, 'Usage: php utils/migrate-namespace/bin/migrate.php [--dry-run] <path>...' . PHP_EOL);
    fwrite(STDERR, 'Try --help for usage.' . PHP_EOL);
    exit(2);
}

exit((new MigrationRunner())->run($paths, $dryRun));
