<?php

declare(strict_types=1);

use Utils\MigrateNamespace\MigrationRunner;
use Utils\MigrateNamespace\SourceCleanGuard;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$usage = <<<'USAGE'
Usage: php utils/migrate-namespace/bin/migrate.php [options] <path>...

Rewrites the Piwik\ root namespace to Matomo\ in PHP source files and, when
configured, in text files with the given extensions (same value-aware rule,
no per-format parsers).

Options:
  --dry-run            Report files that would change without writing them.
  --check              Verify mode: write nothing; exit 1 when any file would
                       change or could not be read/parsed. Uses the default
                       allowlist unless --allowlist is given.
  --allowlist <path>   Exempt a file or directory (repeatable; replaces the
                       default allowlist). Used by --check and by run/dry-run
                       when --skip-allowlisted is given.
  --skip-allowlisted   Skip allowlisted files when migrating (run/dry-run).
                       Makes re-runs no-ops on migrated trees where the
                       allowlisted Piwik\ text is intentional (e.g. dual-prefix
                       back-compat). Omit on the first migration pass.
  --text-extensions <csv>
                       Comma-separated file extensions (no dot) to migrate as
                       text via the shared swap rule (repeatable; merged and
                       lowercased). E.g. neon,yml,yaml,sh,json.
  --help, -h           Show this help message.

Arguments:
  path        A file or directory (recursively searched for *.php files and
              files with a --text-extensions extension).
USAGE;

$paths = [];
$dryRun = false;
$check = false;
$skipAllowlisted = false;
$allowlist = [];
$textExtensions = [];

$args = array_slice($argv, 1);
$argCount = count($args);

for ($i = 0; $i < $argCount; $i++) {
    $argument = $args[$i];

    if ($argument === '--dry-run') {
        $dryRun = true;

        continue;
    }

    if ($argument === '--check') {
        $check = true;

        continue;
    }

    if ($argument === '--allowlist') {
        if ($i + 1 >= $argCount) {
            fwrite(STDERR, 'Option --allowlist requires a path argument.' . PHP_EOL);
            exit(2);
        }

        $allowlist[] = $args[++$i];

        continue;
    }

    if ($argument === '--text-extensions') {
        if ($i + 1 >= $argCount) {
            fwrite(STDERR, 'Option --text-extensions requires a comma-separated list.' . PHP_EOL);
            exit(2);
        }

        $parts = explode(',', strtolower($args[++$i]));

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part !== '' && !in_array($part, $textExtensions, true)) {
                $textExtensions[] = $part;
            }
        }

        continue;
    }

    if ($argument === '--skip-allowlisted') {
        $skipAllowlisted = true;

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

if ($dryRun && $check) {
    fwrite(STDERR, 'Options --dry-run and --check are mutually exclusive.' . PHP_EOL);
    exit(2);
}

if ($paths === []) {
    fwrite(STDERR, 'Usage: php utils/migrate-namespace/bin/migrate.php [options] <path>...' . PHP_EOL);
    fwrite(STDERR, 'Try --help for usage.' . PHP_EOL);
    exit(2);
}

if ($check) {
    $result = (new SourceCleanGuard())->scan(
        $paths,
        $allowlist === [] ? SourceCleanGuard::defaultAllowlist() : $allowlist,
        $textExtensions
    );

    foreach ($result['violations'] as $violation) {
        fwrite(STDOUT, 'violation: ' . $violation . PHP_EOL);
    }

    foreach ($result['failed'] as $failed) {
        fwrite(STDERR, 'ERROR: ' . $failed . PHP_EOL);
    }

    fwrite(
        STDOUT,
        sprintf('%d violations, %d failed', count($result['violations']), count($result['failed'])) . PHP_EOL
    );

    exit(($result['violations'] === [] && $result['failed'] === []) ? 0 : 1);
}

exit((new MigrationRunner())->run(
    $paths,
    $dryRun,
    $skipAllowlisted ? ($allowlist === [] ? SourceCleanGuard::defaultAllowlist() : $allowlist) : [],
    $textExtensions
));
