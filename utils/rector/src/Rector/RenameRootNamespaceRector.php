<?php

declare(strict_types=1);

namespace Utils\Rector\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Utils\Rector\Namespace\RootNamespace;

/**
 * Rewrites the Piwik\ root namespace in the three places the built-in
 * RenameClassRector / RenameStringRector cannot reach:
 *
 *  1. namespace declarations (`namespace Piwik\Db;` -> `namespace Matomo\Db;`,
 *     and the bare root `namespace Piwik;` -> `namespace Matomo;`), because
 *     RenameClassRector returns null for a plain Name node and only matches
 *     FullyQualified class references;
 *  2. a declared class/interface/trait/enum name whose short name changes — the
 *     facade `class Piwik` (in `namespace Piwik`) -> `class Matomo` — because
 *     RenameClassRector::refactorClassLike renames only `implements`, never the
 *     declared name; and
 *  3. dynamic string forms: FQCN templates (sprintf and interpolated), prefix
 *     fragments used in concatenation, and prefix sentinels used with strpos /
 *     str_contains / str_replace. Interpolated strings are rebuilt as a
 *     concatenation of String_ literals and the interpolated expressions, because
 *     Rector prints InterpolatedStringPart values raw (without escaping), so a
 *     rewritten literal ending in "\" before an interpolated variable would
 *     escape the "$" and break the interpolation.
 *
 * The string forms (3) are guarded by RootNamespace::isNamespacePath(), which
 * requires a leading "Piwik\" backslash and a namespace-path remainder, so
 * display text, URLs, translation keys, and serialize payloads are left
 * untouched. A plain exact class-name string (in the configured map, including
 * the facade Piwik\Piwik) is skipped and left to RenameStringRector, which
 * handles the facade's short-name change as a reference. A leading-backslash
 * FQCN string ('\Piwik\Foo') is not matched by RenameStringRector (its keys
 * have no leading backslash), so an exact mapped class or namespace path in
 * that form is rewritten here directly, preserving the leading backslash.
 * Namespace declarations (1) are renamed by prefix swap, not the map, because every
 * Piwik\-rooted namespace becomes Matomo\-rooted. The declared-name rename (2)
 * is map-driven and fires only when the new short name differs (the facade);
 * every other declared name is unchanged.
 *
 * The original namespace for (2) is captured in (1) before the namespace
 * declaration is rewritten — a node's Scope reflects the already-renamed
 * namespace, so it cannot be used — and reset per file via the current file
 * path. Within a single pass, Namespace_ is entered before its ClassLike
 * children, so the original namespace is always available when the declared
 * name is processed.
 */
final class RenameRootNamespaceRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * The Piwik\X => Matomo\X class map. The keys drive the declared-name
     * skip/exact-class logic; the string-form handler skips exact class-name
     * strings (delegated to RenameStringRector), and the declared-name handler
     * renames only when the mapped short name differs (the facade).
     *
     * @var array<string, string>
     */
    private array $classMap = [];

    /**
     * The original (pre-rename) namespace of the file currently being
     * processed, captured by refactorNamespace() before it rewrites the
     * declaration. Reset whenever the current file path changes.
     */
    private ?string $currentNamespace = null;

    private ?string $currentFilePath = null;

    public function __construct(private readonly ValueResolver $valueResolver)
    {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rewrite the Piwik\ root namespace in namespace declarations, the facade class declaration, and dynamic FQCN string templates / prefix sentinels',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
namespace Piwik\Plugins\Demo;

class Demo
{
    public function build(string $plugin, string $component): string
    {
        return sprintf('Piwik\\Plugins\\%s\\%s', $plugin, $component);
    }
}
CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
namespace Matomo\Plugins\Demo;

class Demo
{
    public function build(string $plugin, string $component): string
    {
        return sprintf('Matomo\\Plugins\\%s\\%s', $plugin, $component);
    }
}
CODE_SAMPLE,
                    ['Piwik\\Plugins\\RealClass\\API' => 'Matomo\\Plugins\\RealClass\\API']
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [
            // Namespace_ before ClassLike so the declared-name handler can read
            // the original namespace captured by refactorNamespace().
            Namespace_::class,
            ClassLike::class,
            String_::class,
            InterpolatedString::class,
        ];
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof Namespace_) {
            return $this->refactorNamespace($node);
        }

        if ($node instanceof ClassLike) {
            return $this->refactorClassLike($node);
        }

        if ($node instanceof String_) {
            return $this->refactorString($node);
        }

        if ($node instanceof InterpolatedString) {
            return $this->refactorInterpolatedString($node);
        }

        return null;
    }

    /**
     * @param mixed[] $configuration
     */
    public function configure(array $configuration): void
    {
        $this->classMap = $configuration;
    }

    /**
     * Rewrite the namespace declaration name by prefix swap. Every
     * Piwik\-rooted namespace becomes Matomo\-rooted, including the bare root.
     * Foreign namespaces (Utils\Rector\..., Matomo\Ini) are left untouched. The
     * original name is captured first so refactorClassLike() can form the
     * pre-rename FQCN for the map lookup.
     */
    private function refactorNamespace(Namespace_ $node): ?Namespace_
    {
        $this->resetStateForCurrentFile();

        if ($node->name === null) {
            $this->currentNamespace = null;

            return null;
        }

        $name = $node->name->toString();
        $this->currentNamespace = $name;

        $rewritten = RootNamespace::rewriteNamespaceName($name);

        if ($rewritten === null || $rewritten === $name) {
            return null;
        }

        $node->name = new Name($rewritten);

        return $node;
    }

    /**
     * Rename a declared class/interface/trait/enum name when the mapped FQCN's
     * short name differs. Only the facade qualifies (Piwik\Piwik -> Matomo\Matomo);
     * every other declared name keeps its short name. The original namespace is
     * the one captured by refactorNamespace() before it rewrote the declaration.
     */
    private function refactorClassLike(ClassLike $node): ?ClassLike
    {
        $this->resetStateForCurrentFile();

        if ($node->name === null) {
            return null;
        }

        $namespace = $this->currentNamespace;
        $oldFqcn = $namespace !== null && $namespace !== ''
            ? $namespace . '\\' . $node->name->toString()
            : $node->name->toString();

        if (!isset($this->classMap[$oldFqcn])) {
            return null;
        }

        $newFqcn = $this->classMap[$oldFqcn];
        $backslashPos = strrpos($newFqcn, '\\');
        $newShort = $backslashPos === false ? $newFqcn : substr($newFqcn, $backslashPos + 1);

        if ($newShort === $node->name->toString()) {
            return null;
        }

        $node->name = new Identifier($newShort);

        return $node;
    }

    private function refactorString(String_ $node): ?String_
    {
        $value = $this->valueResolver->getValue($node);

        if (!is_string($value)) {
            return null;
        }

        $rewritten = $this->rewriteIfNamespacePath($value);

        if ($rewritten === null || $rewritten === $value) {
            return null;
        }

        return new String_($rewritten);
    }

    private function refactorInterpolatedString(InterpolatedString $node): ?Node
    {
        if (!$this->interpolatedStringNeedsRewrite($node)) {
            return null;
        }

        // Rector prints InterpolatedStringPart values raw (no escaping), so a
        // rewritten literal ending in "\" before an interpolated variable would
        // escape the "$" and break the interpolation ("Piwik\\Plugins\\$x" would
        // become "Matomo\Plugins\$x", whose $x is no longer interpolated). Rebuild
        // the string as a concatenation of String_ literals and the interpolated
        // expressions: each String_ is escaped correctly by the printer, so the
        // semantics are preserved. RenameStringRector does not reach
        // InterpolatedString parts, so exact class names in the map (the facade)
        // are rewritten here too.
        $expressions = [];
        foreach ($node->parts as $part) {
            if ($part instanceof InterpolatedStringPart) {
                $value = $this->rewriteInterpolatedPartValue($part->value);

                if ($value !== '') {
                    $expressions[] = new String_($value);
                }
            } else {
                $expressions[] = $part;
            }
        }

        if ($expressions === []) {
            return null;
        }

        $result = $expressions[0];
        $count = count($expressions);

        for ($i = 1; $i < $count; ++$i) {
            $result = new Concat($result, $expressions[$i]);
        }

        return $result;
    }

    /**
     * True when any literal part of the interpolated string is a Piwik\ namespace
     * path or an exact mapped class name (plain or leading-backslash), so the
     * string must be rebuilt. RenameStringRector does not reach interpolated
     * parts, so a leading-backslash FQCN part must be rewritten here too.
     */
    private function interpolatedStringNeedsRewrite(InterpolatedString $node): bool
    {
        foreach ($node->parts as $part) {
            if (!$part instanceof InterpolatedStringPart) {
                continue;
            }

            [$core] = $this->splitLeadingBackslash($part->value);

            if (isset($this->classMap[$core]) || RootNamespace::isNamespacePath($core)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rewrite an interpolated-string literal part: an exact mapped class (the
     * facade) takes its mapped value; a Piwik\ namespace path is prefix-swapped;
     * anything else (display text, URL, translation key) is left unchanged. A
     * leading backslash is preserved. RenameStringRector does not reach these
     * parts, so both plain and leading-backslash exact mapped classes are
     * rewritten here (no delegation).
     */
    private function rewriteInterpolatedPartValue(string $value): string
    {
        [$core, $leadingBackslash] = $this->splitLeadingBackslash($value);

        if (isset($this->classMap[$core])) {
            return $this->withLeadingBackslash($this->classMap[$core], $leadingBackslash);
        }

        if (RootNamespace::isNamespacePath($core)) {
            return $this->withLeadingBackslash(RootNamespace::rewriteRoot($core), $leadingBackslash);
        }

        return $value;
    }

    /**
     * Rewrite the leading Piwik\ root of a plain string literal, including the
     * FQCN form with a leading backslash ('\Piwik\Foo'). A plain exact mapped
     * class (no leading backslash) is delegated to RenameStringRector (null); a
     * leading-backslash exact mapped class is rewritten here, because
     * RenameStringRector's keys have no leading backslash. A Piwik\ namespace
     * path (plain or leading-backslash) is prefix-swapped, preserving the
     * leading backslash. Returns null for anything else (display text, URL,
     * translation key, serialize payload).
     */
    private function rewriteIfNamespacePath(string $value): ?string
    {
        [$core, $leadingBackslash] = $this->splitLeadingBackslash($value);

        if (isset($this->classMap[$core])) {
            return $leadingBackslash ? '\\' . $this->classMap[$core] : null;
        }

        if (!RootNamespace::isNamespacePath($core)) {
            return null;
        }

        return $this->withLeadingBackslash(RootNamespace::rewriteRoot($core), $leadingBackslash);
    }

    /**
     * Strip an optional single leading backslash from $value. A leading
     * backslash marks the FQCN string form ('\Piwik\Foo'); the built-in
     * RenameStringRector does not match it (its keys have no leading backslash),
     * so the custom rule rewrites such strings directly. The returned core has
     * no leading backslash, so the classMap lookup and isNamespacePath() /
     * rewriteRoot() operate on the same Piwik\-rooted form as a plain string.
     *
     * @return array{0: string, 1: bool} [core, hadLeadingBackslash]
     */
    private function splitLeadingBackslash(string $value): array
    {
        if ($value !== '' && $value[0] === '\\') {
            return [substr($value, 1), true];
        }

        return [$value, false];
    }

    /**
     * Prepend a leading backslash to $rewritten when $leadingBackslash is true,
     * preserving the FQCN string form. Shared by the plain-string and
     * interpolated-string rewriters.
     */
    private function withLeadingBackslash(string $rewritten, bool $leadingBackslash): string
    {
        return $leadingBackslash ? '\\' . $rewritten : $rewritten;
    }

    /**
     * Reset the captured namespace whenever the file being processed changes,
     * so a class in a file without a namespace declaration does not inherit the
     * previous file's namespace.
     */
    private function resetStateForCurrentFile(): void
    {
        $filePath = $this->getFile()->getFilePath();

        if ($filePath !== $this->currentFilePath) {
            $this->currentFilePath = $filePath;
            $this->currentNamespace = null;
        }
    }
}
