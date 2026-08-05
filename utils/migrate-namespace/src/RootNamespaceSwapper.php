<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

/**
 * Style-preserving `Piwik\` -> `Matomo\` root-namespace prefix swap.
 *
 * This is the standalone migration engine: a plain {@see NodeVisitorAbstract}
 * run through a {@see \PhpParser\NodeTraverser} with a {@see \PhpParser\NodeVisitor\CloningVisitor}
 * and printed via {@see \PhpParser\PrettyPrinter\Standard::printFormatPreserving()}.
 *
 * Format-preserving output. The visitor mutates cloned nodes in place (setting
 * the `Name::$name` string, or the doc-comment text) and returns them.
 * `printFormatPreserving()` then re-emits only the changed nodes, preserving
 * the original file's whitespace, indentation, comments, and layout, so the
 * diff is exactly the `Piwik\` -> `Matomo\` prefix changes — no reformatting.
 *
 * Scope. Every position a `Piwik\` root-namespace reference can appear is
 * covered: namespace declarations, `use` imports, the facade class declaration,
 * code class-name references (extends/implements/typehints/`new`/static
 * calls/instanceof/`::class`/catch/FQ function-call names — relative and
 * fully-qualified), PHPDoc text, and string literals (plain, interpolated,
 * dynamic `sprintf`/`strpos` templates and prefix sentinels). The facade
 * `Piwik\Piwik` -> `Matomo\Matomo` (the one short-name change) is honoured.
 * Display text, URLs, and translation keys containing "Piwik" are left
 * untouched: a reference is only rewritten when it starts with `Piwik\` (or
 * `\Piwik\`), so prose "Piwik" and mid-identifier segments (`SomePiwik\Foo`)
 * are preserved.
 *
 * Idempotent. Running the swapper on already-`Matomo\` source is a no-op: the
 * prefix guards (`Piwik\` / bare `Piwik`) match nothing, so no node changes and
 * the output is byte-identical to the input.
 *
 * @internal Tooling for the `Piwik\` -> `Matomo\` root-namespace migration.
 */
final class RootNamespaceSwapper extends NodeVisitorAbstract
{
    /**
     * `Piwik\` class => `Matomo\` class for classes whose short name changes
     * during the migration (the facade: `Piwik\Piwik` -> `Matomo\Matomo`).
     *
     * @var array<string, string>
     */
    private array $exceptionMap;

    /**
     * The current file's namespace (as declared, BEFORE this visitor rewrites
     * it), used by {@see refactorName()} to tell a namespace-relative bare
     * `Piwik` (intended facade, no import) from an imported non-facade class of
     * the same short name. Reset per file in {@see beforeTraverse()}.
     */
    private ?string $currentNamespace = null;

    /**
     * @param array<string, string> $exceptionMap `Piwik\` class => `Matomo\` class
     *        for short-name changes. The facade entry is always in effect; this
     *        parameter exists so tests can exercise the exceptions-map path.
     */
    public function __construct(array $exceptionMap = ['Piwik\\Piwik' => 'Matomo\\Matomo'])
    {
        $this->exceptionMap = $exceptionMap;
    }

    /**
     * Reset per-file state. The runner reuses one visitor across files, so the
     * current-namespace tracker must not leak from a previous file.
     *
     * @param Node[] $nodes
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->currentNamespace = null;
        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        // Rewrite `Piwik\` references in any node's PHPDoc textually. Runs for
        // every node so all doc-comment-bearing statements are covered. Done
        // first so a doc-bearing node that is also a Name/etc. is handled in
        // one visit.
        $docChanged = $this->refactorDocComment($node);

        if ($node instanceof Namespace_) {
            return $this->refactorNamespace($node) ?? ($docChanged ? $node : null);
        }

        if ($node instanceof UseUse) {
            return $this->refactorUseUse($node) ?? ($docChanged ? $node : null);
        }

        if ($node instanceof ClassLike) {
            return $this->refactorClassLike($node) ?? ($docChanged ? $node : null);
        }

        if ($node instanceof Name) {
            return $this->refactorName($node) ?? ($docChanged ? $node : null);
        }

        if ($node instanceof InterpolatedString) {
            return $this->refactorInterpolatedString($node) ?? ($docChanged ? $node : null);
        }

        if ($node instanceof String_) {
            return $this->refactorString($node) ?? ($docChanged ? $node : null);
        }

        return $docChanged ? $node : null;
    }

    /**
     * `namespace Piwik\…;` -> `namespace Matomo\…;` (and the bare root
     * `namespace Piwik;` -> `namespace Matomo;`). Namespace names are never
     * facade-exception entries, so a plain prefix swap applies.
     */
    private function refactorNamespace(Namespace_ $node): ?Node
    {
        // Capture the ORIGINAL namespace (before rewrite) so refactorName() can
        // recognise namespace-relative bare `Piwik`. The NameResolver pass ran on
        // the original tree, so resolvedName attributes use the original name.
        $this->currentNamespace = $node->name instanceof Name ? $node->name->toString() : null;

        if (!$node->name instanceof Name) {
            return null;
        }

        $rewritten = $this->rewriteFqcn($node->name->toString());
        if ($rewritten === null) {
            return null;
        }

        $node->name = new Name($rewritten);
        return $node;
    }

    /**
     * `use Piwik\…;` -> `use Matomo\…;`, honouring the facade exception
     * (`use Piwik\Piwik;` -> `use Matomo\Matomo;`).
     */
    private function refactorUseUse(UseUse $node): ?Node
    {
        $rewritten = $this->rewriteFqcn($node->name->toString());
        if ($rewritten === null) {
            return null;
        }

        $node->name = new Name($rewritten);
        return $node;
    }

    /**
     * The facade class declaration `class Piwik` (in `namespace Piwik`) ->
     * `class Matomo`. Every other declaration keeps its short name; only its
     * namespace (handled by {@see refactorNamespace()}) changes.
     */
    private function refactorClassLike(ClassLike $node): ?Node
    {
        $namespacedName = $node->namespacedName ?? null;
        if (!$namespacedName instanceof Name) {
            return null;
        }

        $fqcn = $namespacedName->toString();
        if (!isset($this->exceptionMap[$fqcn])) {
            return null;
        }

        if (!$node->name instanceof Identifier) {
            return null;
        }

        $target = $this->exceptionMap[$fqcn];
        $shortName = substr($target, strrpos($target, '\\') + 1) ?: $target;
        if ($node->name->name === $shortName) {
            return null;
        }

        $node->name = new Identifier($shortName);
        return $node;
    }

    /**
     * Code class-name references: extends/implements/typehints/`new`/static
     * calls/instanceof/`::class`/catch/FQ refs/FQ function-call names. Swaps the
     * `Piwik\` prefix to `Matomo\`, preserving the node's qualification (relative
     * `Name` vs leading-backslash `FullyQualified`). The facade exception is
     * honoured, including the bare facade called unqualified (`Piwik::` -> `Matomo::`).
     *
     * The name string is replaced in place on the existing node so its
     * qualification type (Name vs FullyQualified) is preserved by the
     * format-preserving printer.
     */
    private function refactorName(Name $node): ?Name
    {
        // A bare `Piwik` (no `Piwik\` prefix) is the facade in two cases:
        //   1. it resolves to `Piwik\Piwik` (a `use Piwik\Piwik;` import), or
        //   2. it is namespace-relative (no import): resolvedName is
        //      `<currentNamespace>\Piwik`, which does not name a real class —
        //      the call is intended as the facade (a pre-existing missing-import
        //      bug, fatal if executed, unchanged by the rename). Both rename.
        // A bare `Piwik` that resolves to an IMPORTED non-facade class of the
        // same short name (e.g. LogViewer's log parser `use ...\Parser\Piwik`)
        // must NOT be renamed: its resolvedName is a different FQCN. The
        // NameResolver pass in the runner sets `resolvedName` (replaceNodes off).
        if ($node->toString() === 'Piwik') {
            $resolved = $node->getAttribute('resolvedName');
            $resolvedStr = $resolved instanceof Name ? $resolved->toString() : null;
            $isFacadeImport = $resolvedStr === 'Piwik\\Piwik' || $resolvedStr === 'Matomo\\Matomo';
            $isNamespaceRelative = $this->currentNamespace !== null
                && $resolvedStr === $this->currentNamespace . '\\Piwik';
            if (!$isFacadeImport && !$isNamespaceRelative) {
                // An imported non-facade class named `Piwik`: leave it.
                return null;
            }
        }

        $rewritten = $this->rewriteFqcn($node->toString());
        if ($rewritten === null) {
            return null;
        }

        $node->name = ltrim($rewritten, '\\');
        return $node;
    }

    /**
     * Rewrites the literal parts of an interpolated string. Returns the
     * (mutated) node when any part changed; null otherwise.
     *
     * The format-preserving printer cannot reprint a single changed
     * `InterpolatedStringPart` in isolation: there is no `pInterpolatedStringPart`
     * handler (parts are only printable via the parent `pScalar_InterpolatedString`
     * -> `pEncapsList`, which reads `value` directly). When a part changes, the
     * per-part fallback would call the missing handler and crash. Dropping the
     * `origNode` link on the parent forces the whole string to be reprinted via
     * `pScalar_InterpolatedString`, which re-emits all parts correctly. The
     * `kind`/`docLabel` attributes are preserved, so heredoc vs double-quoted
     * formatting is retained.
     */
    private function refactorInterpolatedString(InterpolatedString $node): ?Node
    {
        $changed = false;
        foreach ($node->parts as $part) {
            if (!$part instanceof InterpolatedStringPart) {
                continue;
            }

            $rewritten = $this->rewriteValue($part->value);
            if ($rewritten !== null) {
                $part->value = $rewritten;
                $changed = true;
            }
        }

        if (!$changed) {
            return null;
        }

        // Force the parent to reprint as a whole (see method doc).
        $node->setAttribute('origNode', null);
        return $node;
    }

    private function refactorString(String_ $node): ?Node
    {
        $rewritten = $this->rewriteValue($node->value);
        if ($rewritten === null) {
            return null;
        }

        $node->value = $rewritten;
        return $node;
    }

    /**
     * Rewrites `Piwik\` references in a node's PHPDoc comment textually. The
     * migration treats PHPDoc as plain text: `\Piwik\Foo` -> `\Matomo\Foo`,
     * relative `Piwik\Foo` -> `Matomo\Foo`, and `{@link Piwik\Foo Foo}` prose,
     * all by the same guarded prefix swap used for code. Returns true when the
     * doc comment was changed; false when there was no doc comment or no
     * `Piwik\` reference in it.
     *
     * The substitution is prefix-guarded: it only rewrites a `Piwik\` that starts
     * a class-name token (preceded by a non-identifier boundary), so
     * mid-identifier segments (`SomePiwik\Foo`) and bare prose "Piwik" are left
     * untouched. The facade `Piwik\Piwik` -> `Matomo\Matomo` is handled
     * explicitly so the second segment is not left as `Piwik`.
     */
    private function refactorDocComment(Node $node): bool
    {
        $doc = $node->getDocComment();
        if (!$doc instanceof Doc) {
            return false;
        }

        $text = $doc->getText();
        if (strpos($text, 'Piwik\\') === false) {
            return false;
        }

        $pattern = '/(?<![A-Za-z0-9_])Piwik(\\\\+)(Piwik|[A-Z])/';
        $rewritten = preg_replace_callback(
            $pattern,
            function (array $m): string {
                // $m[1] is the captured backslash run; reproduce it
                // so regex-escaped forms (Piwik\\) are preserved.
                $bs = $m[1];
                if ($m[2] === 'Piwik') {
                    return 'Matomo' . $bs . 'Matomo';
                }
                return 'Matomo' . $bs . $m[2];
            },
            $text
        );

        if ($rewritten === null || $rewritten === $text) {
            return false;
        }

        $node->setDocComment(new Doc($rewritten));
        return true;
    }

    /**
     * Rewrites a fully-qualified name (a namespace or use-import name) to its
     * `Matomo\` counterpart. Returns the rewritten name, or null when the name
     * is not a `Piwik\` namespace reference.
     *
     * Unlike {@see rewriteValue()}, this also rewrites the bare root `Piwik`
     * (the `namespace Piwik;` declaration and the bare facade call `Piwik::`),
     * because a namespace/facade name may be a single segment.
     */
    private function rewriteFqcn(string $name): ?string
    {
        if (isset($this->exceptionMap[$name])) {
            return $this->exceptionMap[$name];
        }

        // Bare root namespace or bare facade: `Piwik` -> `Matomo`.
        if ($name === 'Piwik') {
            return 'Matomo';
        }

        // Prefixed: `Piwik\…` -> `Matomo\…`. `Piwik\` is 6 characters.
        if (strncmp($name, 'Piwik\\', 6) !== 0) {
            return null;
        }

        return 'Matomo\\' . substr($name, 6);
    }

    /**
     * Rewrites the root-namespace prefix of a string literal's value. Returns
     * the rewritten value, or null when the value is not a `Piwik\` namespace
     * reference (display text, URLs, translation keys) or is a mid-string segment
     * such as `SomePiwik\Foo`.
     *
     * Bare `Piwik` is intentionally NOT rewritten here: a literal "Piwik" is
     * display text, not a namespace declaration.
     */
    private function rewriteValue(string $value): ?string
    {
        // The exceptions map and the prefix check both key off the bare
        // (leading-backslash-stripped) form, so a fully-qualified literal such
        // as `'\Piwik\Plugins\%s\API'` or `['\Piwik\Piwik', 'translate']` is
        // rewritten the same as its plain counterpart. The original leading
        // backslash (if any) is preserved on the result.
        $hasLeadingBackslash = $value !== '' && $value[0] === '\\';
        $bare = $hasLeadingBackslash ? substr($value, 1) : $value;

        if (isset($this->exceptionMap[$bare])) {
            return ($hasLeadingBackslash ? '\\' : '') . $this->exceptionMap[$bare];
        }

        // Only the root-namespace position is rewritten: the bare value must
        // start with `Piwik\`. This leaves mid-string segments (SomePiwik\Foo)
        // and non-namespace prose/URLs/translation-keys (which never start with
        // `Piwik\`) untouched.
        if (strncmp($bare, 'Piwik\\', 6) === 0) {
            return ($hasLeadingBackslash ? '\\' : '') . 'Matomo\\' . substr($bare, 6);
        }

        // 3. `Piwik\` embedded in the literal at a non-identifier boundary
        //    (regex patterns, error messages, cron strings, code templates).
        //    Rewrite each `Piwik\` starting a class-name token, preserving the
        //    backslash count. The facade `Piwik\Piwik` is honoured.
        if (strpos($value, 'Piwik\\') === false) {
            return null;
        }
        $pattern = '/(?<![A-Za-z0-9_])Piwik(\\\\+)(Piwik|[A-Z])/';
        $rewritten = preg_replace_callback(
            $pattern,
            function (array $m): string {
                // $m[1] is the captured backslash run; reproduce it.
                $bs = $m[1];
                if ($m[2] === 'Piwik') {
                    return 'Matomo' . $bs . 'Matomo';
                }
                return 'Matomo' . $bs . $m[2];
            },
            $value
        );
        if ($rewritten === null || $rewritten === $value) {
            return null;
        }
        return $rewritten;
    }
}
