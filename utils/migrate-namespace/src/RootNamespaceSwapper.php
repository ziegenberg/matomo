<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Rewrites Piwik\ -> Matomo\ in code names only, by mutating leaf string
 * properties in place (Name::$name and Identifier::$name).
 *
 * The swapper must always run AFTER PhpParser\NodeVisitor\CloningVisitor in the
 * same NodeTraverser, so every node it receives is a clone carrying an
 * 'origNode' attribute pointing at the original. Only leaf strings are mutated;
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
     */
    public function __construct(array $aliasMap)
    {
        $this->aliasMap = $aliasMap;
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
                $map[$namespace] = self::collectUseAliases($stmt->stmts ?? []);
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
        if ($node instanceof Node\Name) {
            $this->handleName($node);
        } elseif ($node instanceof Node\Identifier) {
            $this->handleIdentifier($node);
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
