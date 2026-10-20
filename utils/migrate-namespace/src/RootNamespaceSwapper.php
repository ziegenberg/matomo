<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Rewrites Piwik\ -> Matomo\ in code names, string literals, and PHPDoc
 * comments, by mutating leaf values in place (Name::$name, Identifier::$name,
 * Scalar\String_::$value, and Doc comment text).
 *
 * The swapper must always run AFTER PhpParser\NodeVisitor\CloningVisitor in the
 * same NodeTraverser, so every node it receives is a clone carrying an
 * 'origNode' attribute pointing at the original. Only leaf values are mutated;
 * subnodes are never replaced and children are never touched from a parent's
 * enterNode (children are still the originals at that point and mutating them
 * would silently lose the swap in printFormatPreserving).
 *
 * Scoping rules:
 *  - Namespace declarations are always swapped (a `namespace Piwik;`
 *    declaration is never a reference), including the bracketed form.
 *  - The facade Piwik\Piwik -> Matomo\Matomo is swapped in both segments,
 *    including its declared class name, but only when declared inside
 *    `namespace Piwik;` (Piwik\Plugins\LogViewer\Log\Parser\Piwik keeps its
 *    short name).
 *  - Multi-segment names swap the first segment only.
 *  - Single-segment Piwik references (e.g. `Piwik::get()`) are swapped only
 *    when the per-namespace alias map has no conflicting `use` alias for
 *    `Piwik`. An alias to Piwik\Piwik (the facade, e.g. `use Piwik\Piwik;`)
 *    still allows the swap; any other alias (e.g. `use Piwik\Cache as Piwik;`)
 *    blocks it, so the reference keeps resolving to the same class after the
 *    import is swapped to Matomo\Cache as Piwik.
 *  - Strings and docblocks are rewritten with RootNamespace::swapQualified(),
 *    the same value-aware boundary rule, without alias-map resolution: only
 *    backslash-qualified Piwik\ roots are swapped, so bare 'Piwik', display
 *    strings, 'PiwikPro', 'PIWIK_*' names, and alias names (PiwikCache) are
 *    left untouched, while 'Piwik\Cache' still swaps (the class moved). The
 *    original quoting and escaping of changed string literals is preserved
 *    byte-for-byte via the raw token span. Interpolated strings are
 *    deliberately not rewritten: php-parser has no printer method for a
 *    mutated InterpolatedStringPart, so format-preserving printing would
 *    crash. There are no interpolated-string occurrences in the repo, so this
 *    is an accepted residual.
 *  - Plain comments (line and block comments) are never rewritten; docblocks
 *    are swapped only when their text actually differs, keeping unchanged
 *    files byte-identical.
 *
 * Known residual (accepted per spec): a bare single-segment `Piwik` reference
 * with no use statement resolves to the current namespace's Piwik class; there
 * is no class map, so it is swapped anyway. The alias-guard-only design is the
 * spec'd boundary; diff review is the backstop.
 */
final class RootNamespaceSwapper extends NodeVisitorAbstract
{
    /**
     * Alias map per namespace scope: namespace name ('' for global) =>
     * alias => fully qualified target of the use declaration.
     *
     * @var array<string, array<string, string>>
     */
    private array $aliasMap;

    /**
     * Parser token array, used to recover the raw literal of changed strings
     * so the original quoting and escaping can be preserved.
     *
     * @var \PhpParser\Token[]
     */
    private array $tokens;

    /**
     * Stack of enclosing namespace names ('' for global/bracketed-global).
     *
     * @var list<string>
     */
    private array $namespaceStack = [];

    /**
     * Stack of every visited node, used to find the parent (the 'parent'
     * attribute is not enabled; the traverser does not set it).
     *
     * @var list<Node>
     */
    private array $nodeStack = [];

    /**
     * @param array<string, array<string, string>> $aliasMap Map built by buildAliasMap()
     * @param \PhpParser\Token[] $tokens Parser token array, optional; used only for raw-literal recovery.
     */
    public function __construct(array $aliasMap, array $tokens = [])
    {
        $this->aliasMap = $aliasMap;
        $this->tokens = $tokens;
    }

    /**
     * Builds the alias map from use declarations of the original AST.
     *
     * Use declarations only appear at the top level of a file or directly
     * inside a namespace block, so only those two levels are scanned. The map
     * is keyed by namespace name ('' for the global scope), so multi-namespace
     * files are handled per block.
     *
     * @param Node\Stmt[] $stmts
     * @return array<string, array<string, string>>
     */
    public static function buildAliasMap(array $stmts): array
    {
        $map = ['' => self::collectUseAliases($stmts)];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Namespace_) {
                $namespace = $stmt->name?->toString() ?? '';
                $map[$namespace] = self::collectUseAliases($stmt->stmts);
            }
        }

        return $map;
    }

    /**
     * @param Node\Stmt[] $stmts
     * @return array<string, string>
     */
    private static function collectUseAliases(array $stmts): array
    {
        $aliases = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Use_) {
                foreach ($stmt->uses as $item) {
                    $alias = $item->alias?->toString() ?? $item->name->getLast();
                    $aliases[$alias] = $item->name->toString();
                }

                continue;
            }

            if ($stmt instanceof Node\Stmt\GroupUse) {
                // Group-use items are single-segment names relative to the
                // prefix; resolving them against the prefix is required so
                // `use Piwik\Foo\{Piwik};` maps Piwik -> Piwik\Foo\Piwik while
                // `use Piwik\{Piwik};` maps Piwik -> Piwik\Piwik.
                $prefix = $stmt->prefix->toString();

                foreach ($stmt->uses as $item) {
                    $alias = $item->alias?->toString() ?? $item->name->getLast();
                    $aliases[$alias] = $prefix . '\\' . $item->name->toString();
                }
            }
        }

        return $aliases;
    }

    public function enterNode(Node $node)
    {
        $this->handleDocComments($node);

        if ($node instanceof Node\Name) {
            $this->handleName($node);
        } elseif ($node instanceof Node\Identifier) {
            $this->handleIdentifier($node);
        } elseif ($node instanceof Node\Scalar\String_) {
            $this->handleString($node);
        }

        $this->nodeStack[] = $node;

        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespaceStack[] = $node->name?->toString() ?? '';
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        array_pop($this->nodeStack);

        if ($node instanceof Node\Stmt\Namespace_) {
            array_pop($this->namespaceStack);
        }

        return null;
    }

    private function handleName(Node\Name $node): void
    {
        $parts = $node->getParts();

        if ($parts[0] !== RootNamespace::OLD_ROOT) {
            return;
        }

        if ($parts === [RootNamespace::OLD_ROOT, RootNamespace::OLD_ROOT]) {
            // Piwik\Piwik -> Matomo\Matomo: the facade, short name included.
            $node->name = RootNamespace::FACADE_NEW;

            return;
        }

        if (count($parts) === 1) {
            $parent = $this->parent();

            // A namespace declaration is never a reference; swap it
            // unconditionally. (The CloningVisitor has already installed its
            // clone as the parent's name subnode, so identity holds here.)
            if ($parent instanceof Node\Stmt\Namespace_ && $parent->name === $node) {
                $node->name = RootNamespace::NEW_ROOT;

                return;
            }

            // Single-segment reference: swap unless a use alias shadows the
            // Piwik name. An alias to Piwik\Piwik (the facade) is consistent
            // with the swap; anything else keeps the reference untouched.
            $target = $this->aliasMap[$this->currentNamespace()]['Piwik'] ?? null;

            if ($target === null || $target === RootNamespace::FACADE_OLD) {
                $node->name = RootNamespace::NEW_ROOT;
            }

            return;
        }

        // Multi-segment: swap the root segment only.
        $node->name = RootNamespace::NEW_ROOT . '\\' . implode('\\', array_slice($parts, 1));
    }

    private function handleIdentifier(Node\Identifier $node): void
    {
        $parent = $this->parent();

        if (!$parent instanceof Node\Stmt\ClassLike) {
            return;
        }

        if ($parent->name !== $node || $node->name !== RootNamespace::OLD_ROOT) {
            return;
        }

        // Only the facade declared inside its own root namespace is renamed;
        // Piwik\Plugins\*\Piwik classes keep their short name.
        if ($this->currentNamespace() !== RootNamespace::OLD_ROOT) {
            return;
        }

        $node->name = RootNamespace::NEW_ROOT;
    }

    private function handleString(Node\Scalar\String_ $node): void
    {
        $originalValue = $node->value;
        $newValue = RootNamespace::swapQualified($originalValue);

        if ($newValue === $originalValue) {
            return;
        }

        $node->value = $newValue;

        $raw = $this->rawLiteral($node);

        if ($raw !== null) {
            $swappedRaw = RootNamespace::swapRawLiteral($raw, $originalValue);

            if ($swappedRaw !== null) {
                $node->setAttribute('rawValue', $swappedRaw);

                return;
            }
        }

        // No swapped raw literal available (tokens absent, or escape sequences
        // that cannot be paired): drop the parser-set rawValue so the printer
        // canonicalizes the swapped value instead of emitting the stale
        // original literal.
        $node->setAttribute('rawValue', null);
    }

    /**
     * Swaps backslash-qualified Piwik\ roots in every Doc comment of the node.
     *
     * Plain comments (line and block comments) are never rewritten. Doc
     * comments are replaced by new Comment\Doc instances (carrying the
     * original positions) only when their text differs, so the printer's
     * comment identity check keeps unchanged comments in the token-spliced
     * original bytes.
     */
    private function handleDocComments(Node $node): void
    {
        $comments = $node->getComments();

        if ($comments === []) {
            return;
        }

        $newComments = null;

        foreach ($comments as $index => $comment) {
            if (!$comment instanceof Comment\Doc) {
                continue;
            }

            $newText = RootNamespace::swapQualified($comment->getText());

            if ($newText === $comment->getText()) {
                continue;
            }

            if ($newComments === null) {
                $newComments = $comments;
            }

            $newComments[$index] = new Comment\Doc(
                $newText,
                $comment->getStartLine(),
                $comment->getStartFilePos(),
                $comment->getStartTokenPos(),
                $comment->getEndLine(),
                $comment->getEndFilePos(),
                $comment->getEndTokenPos()
            );
        }

        if ($newComments !== null) {
            $node->setAttribute('comments', $newComments);
        }
    }

    /**
     * The raw source literal of a string node, or null when unavailable.
     *
     * Joins the token texts over the original node's start..end token span
     * (inclusive), which covers single- and double-quoted strings (including
     * the b'' prefix) as well as heredoc and nowdoc delimiters. When tokens
     * are absent the caller leaves the string to canonical re-printing, which
     * is still value-correct.
     */
    private function rawLiteral(Node\Scalar\String_ $node): ?string
    {
        if ($this->tokens === []) {
            return null;
        }

        $origNode = $node->getAttribute('origNode');

        if (!$origNode instanceof Node) {
            return null;
        }

        $start = $origNode->getStartTokenPos();
        $end = $origNode->getEndTokenPos();

        if ($start < 0 || $end < $start) {
            return null;
        }

        $raw = '';

        for ($i = $start; $i <= $end; $i++) {
            $raw .= $this->tokens[$i]->text;
        }

        return $raw;
    }

    private function parent(): ?Node
    {
        $parent = end($this->nodeStack);

        return $parent === false ? null : $parent;
    }

    private function currentNamespace(): string
    {
        $top = end($this->namespaceStack);

        return $top === false ? '' : $top;
    }
}
