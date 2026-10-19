# Migrate Namespace

Standalone, deterministic migration tool that rewrites the `Piwik\` root namespace to `Matomo\` in PHP source files: namespace declarations, `use` imports, the `Piwik\Piwik` facade, and every code position (extends/implements/typehints/`new`/static calls/`instanceof`/`::class`/catch/FQ function names). Relative vs fully-qualified reference shape and file formatting are preserved.

## Usage

```
php utils/migrate-namespace/bin/migrate.php [--dry-run] <path>...
php utils/migrate-namespace/bin/migrate.php --help
```

- `--dry-run` reports files that would change without writing them.
- Only changed files are written. Unchanged files are left byte-identical.
- Git submodule checkouts (directories whose `.git` is a file, e.g. `plugins/LogViewer`) are never touched — they are separate repositories. `vendor/`, `.git/`, and `node_modules/` are skipped too.
- Exit code 0 when every file was processed; 1 when any file could not be parsed or written; 2 on CLI misuse.

## Tests

The tool has its own independent PHPUnit setup and is not wired into the main Matomo test suites (`tests/PHPUnit/`):

```
vendor/bin/phpunit -c utils/migrate-namespace/phpunit.xml.dist
```

Fixture-driven tests cover namespace declarations, `use` imports, the facade declaration, all code `Name` positions, relative vs fully-qualified preservation, the alias-map guard (`use Piwik\Cache as PiwikCache;` is left untouched), and idempotency.

### CI

Temporary GitHub Actions coverage runs these tests automatically via `.github/workflows/migrate-namespace-tests.yml`. It only triggers when files under `utils/migrate-namespace/`, `composer.json`, or `composer.lock` change, so regular PRs are not slowed down.

## Removal after the transition

This tool is deliberately isolated so it can be deleted wholesale once the `Piwik\` → `Matomo\` migration is complete:

1. Delete `utils/migrate-namespace/`.
2. Delete `.github/workflows/migrate-namespace-tests.yml` (temporary CI coverage).
3. Remove the `Utils\MigrateNamespace\` and `Utils\MigrateNamespace\Tests\` entries from `composer.json` `autoload-dev`.
4. Remove `nikic/php-parser` from `require-dev` if nothing else uses it, then refresh `composer.lock`.

Nothing in the main test suite depends on this tool.
