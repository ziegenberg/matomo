# migrate-namespace — `Piwik\` → `Matomo\` root-namespace migration

A deterministic, idempotent, **style-preserving** tool that rewrites `Piwik\`
root-namespace references to `Matomo\` across a PHP source tree. This is the
canonical migration engine for the root-namespace rename.

## Usage

```bash
# Migrate one or more paths (files or directories; .php files under directories)
ddev exec php utils/migrate-namespace/bin/migrate.php core plugins tests

# Single plugin / repo checkout
ddev exec php utils/migrate-namespace/bin/migrate.php plugins/MyPlugin

# Single file
ddev exec php utils/migrate-namespace/bin/migrate.php core/Access.php
```

The runner prints `Migrated N of M file(s).` A second run on already-migrated
source prints `Migrated 0 of M file(s).` — it is a no-op.

## What it rewrites

Every position a `Piwik\` root-namespace reference can appear:

- `namespace Piwik\…;` → `namespace Matomo\…;` (and the bare root `namespace Piwik;`)
- `use Piwik\…;` → `use Matomo\…;`
- the facade class declaration `class Piwik` (in `namespace Piwik`) → `class Matomo`
- code class-name references — extends/implements/typehints/`new`/static calls/
  instanceof/`::class`/catch/FQ function-call names — relative and fully-qualified
- PHPDoc text (`@return`, `@param`, `@throws`, `{@link Piwik\Foo …}`, etc.)
- string literals — plain, interpolated, and dynamic templates
  (`sprintf('Piwik\Plugins\%s', …)`, `strpos($n, 'Piwik\Plugins')`), including
  leading-backslash FQCN strings (`'\Piwik\Plugins\%s\API'`) and callback arrays
  (`['\Piwik\Piwik', 'translate']`)

The facade `Piwik\Piwik` → `Matomo\Matomo` (the one short-name change) is
honoured. Each reference's qualification is preserved: a relative `Piwik\Foo`
stays relative (`Matomo\Foo`); a fully-qualified `\Piwik\Foo` stays
fully-qualified (`\Matomo\Foo`).

## What it leaves untouched

Display text, URLs, and translation keys containing "Piwik" are NOT rewritten: a
reference is only rewritten when it starts with `Piwik\` (or `\Piwik\`), so prose
"Piwik", `Piwik::` in docblock code samples, and mid-identifier segments
(`SomePiwik\Foo`) are preserved. (Bare `Piwik` as a namespace declaration or the
unqualified facade call IS rewritten, via the namespace/facade path.)

## Properties

- **Style-preserving.** Uses PhpParser `CloningVisitor` + `printFormatPreserving()`,
  so only the prefix changes. The file's whitespace, indentation, comments, and
  layout are preserved — the diff is exactly the `Piwik\` → `Matomo\` changes.
- **Idempotent.** Running on already-`Matomo\` source is a no-op (verified: 0
  diff on re-run over `core/`).
- **Valid + clean output.** On `core/`: 731/731 files valid PHP, 0 phpcs
  violations (it is a pure prefix swap of the already-clean base).
- **No class map / no reflection.** It swaps the `Piwik\` prefix directly, so
  every `Piwik\` class is covered without enumerating them.

## Full migration workflow

```bash
# 1. Rename
ddev exec php utils/migrate-namespace/bin/migrate.php core plugins tests

# 2. Regenerate the PHPStan baseline
ddev composer phpstan

# 3. Source-clean guard: no load-bearing Piwik\ in migrated source
ddev matomo:console tests:run --file=tests/PHPUnit/Unit/NoPiwikNamespaceInMigratedSourceTest.php

# 4. Tests
ddev matomo:console tests:run <Plugin>
```

No `phpcbf` step is needed: the swapper preserves the existing style and
introduces no new violations.

## Internals

`RootNamespaceSwapper` is a `NodeVisitorAbstract`. The runner clones the AST
(`CloningVisitor`, which sets `origNode` on every node for format preservation),
runs the swapper, then prints with `printFormatPreserving()`. The swapper
mutates cloned nodes in place (setting `Name::$name`, doc-comment text, or
string values) and returns them.

One edge case: `InterpolatedStringPart` is a leaf with no printer method (parts
are only printable via the parent `pScalar_InterpolatedString` → `pEncapsList`).
When an interpolated string's literal part changes, the swapper drops the
parent's `origNode` link so the whole string reprints cleanly via
`pScalar_InterpolatedString` (see `refactorInterpolatedString`).
