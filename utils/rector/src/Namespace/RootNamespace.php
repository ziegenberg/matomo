<?php

declare(strict_types=1);

namespace Utils\Rector\Namespace;

/**
 * Root-namespace rename constants and the FQCN-string guard shared by the
 * generated class map (which feeds RenameClassRector / RenameStringRector) and
 * the custom RenameRootNamespaceRector.
 *
 * The guard recognises exactly the string forms the built-in rules cannot
 * reach: dynamic FQCN templates (sprintf and interpolated), prefix fragments
 * used in concatenation, and prefix sentinels used with strpos / str_contains /
 * str_replace. The leading "Piwik\" backslash is the discriminator that rejects
 * display text ("Piwik"), URLs ("https://piwik.org"), translation keys
 * ("Piwik_ProfessionalSupport_Title"), and serialize payloads that merely
 * contain "Piwik".
 */
final class RootNamespace
{
    public const OLD_PREFIX = 'Piwik\\';
    public const NEW_PREFIX = 'Matomo\\';

    /**
     * Bare root namespace (no trailing backslash), as written in
     * `namespace Piwik;`. Distinct from OLD_PREFIX because a bare root has no
     * path remainder.
     */
    public const OLD_ROOT = 'Piwik';
    public const NEW_ROOT = 'Matomo';

    /**
     * The facade is the one class whose short name changes: Piwik\Piwik -> Matomo\Matomo.
     * Every other class keeps its short name; only the root prefix is swapped.
     */
    public const FACADE_OLD = 'Piwik\\Piwik';
    public const FACADE_NEW = 'Matomo\\Matomo';

    /**
     * A namespace-path segment: a PHP identifier, a sprintf placeholder, or a
     * variable-interpolation placeholder.
     */
    private const SEGMENT = '[A-Za-z_][A-Za-z0-9_]*|%[sd]|%[0-9]+\$[sd]|\$[A-Za-z_][A-Za-z0-9_]*|\{\$[A-Za-z_][A-Za-z0-9_]*\}|\$\{[A-Za-z_][A-Za-z0-9_]*\}';

    /** @var string|null */
    private static $pattern;

    /**
     * True when $value is a Piwik\-rooted namespace path: an FQCN, a dynamic
     * FQCN template, a prefix fragment (trailing backslash), or a prefix
     * sentinel. The leading "Piwik\" backslash excludes bare "Piwik" (ambiguous
     * product name), display text, URLs, translation keys, and serialize payloads.
     */
    public static function isNamespacePath(string $value): bool
    {
        return preg_match(self::pattern(), $value) === 1;
    }

    /**
     * Swap the leading Piwik\ root for Matomo\. The caller must ensure
     * isNamespacePath($value) is true and that $value is not an exact class
     * name handled by RenameStringRector (e.g. the facade).
     */
    public static function rewriteRoot(string $value): string
    {
        return self::NEW_PREFIX . substr($value, strlen(self::OLD_PREFIX));
    }

    /**
     * Rewrite a namespace declaration name: the bare root `Piwik` becomes
     * `Matomo`, and any `Piwik\...` path becomes `Matomo\...`. Returns null when
     * $name is not a Piwik\-rooted namespace (e.g. `Utils\Rector\...`,
     * `Matomo\Ini`), so foreign namespaces are left untouched. Unlike
     * isNamespacePath()/rewriteRoot(), this also handles the bare root because a
     * `namespace Piwik;` declaration has no path remainder.
     */
    public static function rewriteNamespaceName(string $name): ?string
    {
        if ($name === self::OLD_ROOT) {
            return self::NEW_ROOT;
        }

        if (strpos($name, self::OLD_PREFIX) === 0) {
            return self::rewriteRoot($name);
        }

        return null;
    }

    private static function pattern(): string
    {
        if (self::$pattern === null) {
            $segment = self::SEGMENT;
            // ^Piwik\ ( SEGMENT (\ SEGMENT)* \? | empty )
            // The leading backslash is required so bare "Piwik" is rejected.
            self::$pattern = '~^Piwik\\\\(?:(' . $segment . ')(?:\\\\(' . $segment . '))*\\\\?|)$~';
        }

        return self::$pattern;
    }
}
