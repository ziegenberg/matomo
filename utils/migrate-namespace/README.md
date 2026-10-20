# Migrate Namespace

Standalone, deterministic migration tool that rewrites the `Piwik\` root namespace to `Matomo\` in PHP source files: namespace declarations, `use` imports, the `Piwik\Piwik` facade, every code position (extends/implements/typehints/`new`/static calls/`instanceof`/`::class`/catch/FQ function names), backslash-qualified `Piwik\` prefixes in string literals, and PHPDoc types. Relative vs fully-qualified reference shape and file formatting are preserved.

String literals keep their original quoting and escaping byte-for-byte (`'Piwik\\Plugins\\%s'` becomes `'Matomo\\Plugins\\%s'`, not `'Matomo\Plugins\%s'`), and docblocks are swapped only when their text actually differs, so unchanged files stay byte-identical. Plain comments (`//`, `/* */`) and interpolated strings (`"Piwik\Foo $x"`) are deliberately left untouched.

## Usage

```
php utils/migrate-namespace/bin/migrate.php [--dry-run] <path>...
php utils/migrate-namespace/bin/migrate.php --check [--allowlist <path>] <path>...
php utils/migrate-namespace/bin/migrate.php --help
```

- `--dry-run` reports files that would change without writing them.
- `--check` verifies: writes nothing and exits 1 when any file would change or could not be read/parsed. It uses the default allowlist unless `--allowlist` (repeatable, replaces the default) is given.
- Only changed files are written. Unchanged files are left byte-identical.
- Git submodule checkouts (directories whose `.git` is a file, e.g. `plugins/LogViewer`) are never touched — they are separate repositories. `vendor/`, `.git/`, and `node_modules/` are skipped too.
- Exit code 0 when every file was processed; 1 when any file could not be parsed or written (or, with `--check`, when any file would change); 2 on CLI misuse.

### Source-clean check

`migrate.php --check` verifies that scanned PHP files contain no `Piwik\` root-namespace references that the migration would rewrite. The check is idempotency-based: a file is a violation exactly when running the migrator over it changes it, so it flags precisely what the swapper would rewrite (code names, string literals, docblock types) while plain comments, display strings, `PIWIK_*` names, and alias-guarded references (`use Matomo\Cache as Piwik;` plus `Piwik::` calls) stay clean by construction.

The default allowlist exempts the files that legitimately contain `Piwik\` text: `LegacyAutoloader.php`, `tests/PHPUnit/Unit/LegacyAutoLoaderTest.php`, `tests/PHPUnit/Unit/PluginNamespaceAutoloadTest.php`, `plugins/Diagnostics/`, and the tool itself (`utils/migrate-namespace/`). `--allowlist` accepts an explicit allowlist for other uses (e.g. subtree scans).

## Tests

The tool has its own independent PHPUnit setup and is not wired into the main Matomo test suites (`tests/PHPUnit/`):

```
vendor/bin/phpunit -c utils/migrate-namespace/phpunit.xml.dist
```

Fixture-driven tests cover namespace declarations, `use` imports, the facade declaration, all code `Name` positions, relative vs fully-qualified preservation, the alias-map guard (`use Piwik\Cache as PiwikCache;` is left untouched), string literals (escaping preservation, regex strings, heredoc/nowdoc, sentinels), docblocks, interpolated-string exclusion, and idempotency.

`SourceCleanGuardTest` covers the guard with temp trees and includes `testCoreTreeIsCleanWhenEnabled`, a real-tree scan of `core/`, `plugins/`, and `tests/` against the default allowlist. It is skipped unless `MIGRATE_NAMESPACE_GUARD_CORE=1` is set and will be flipped on by the follow-up migration issues once those trees are clean.

### CI

Temporary GitHub Actions coverage runs these tests automatically via `.github/workflows/migrate-namespace-tests.yml`. It only triggers when files under `utils/migrate-namespace/`, `composer.json`, or `composer.lock` change, so regular PRs are not slowed down.

## Removal after the transition

This tool is deliberately isolated so it can be deleted wholesale once the `Piwik\` → `Matomo\` migration is complete:

1. Delete `utils/migrate-namespace/`.
2. Delete `.github/workflows/migrate-namespace-tests.yml` (temporary CI coverage).
3. Remove the `Utils\MigrateNamespace\` and `Utils\MigrateNamespace\Tests\` entries from `composer.json` `autoload-dev`.
4. Remove `nikic/php-parser` from `require-dev` if nothing else uses it, then refresh `composer.lock`.

Nothing in the main test suite depends on this tool.
