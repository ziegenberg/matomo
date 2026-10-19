<?php

declare(strict_types=1);

namespace Utils\MigrateNamespace;

/**
 * Root-namespace migration constants.
 *
 * The migration renames the PHP root namespace Piwik\ -> Matomo\ in code names
 * only. The facade class Piwik\Piwik -> Matomo\Matomo is the one class whose
 * short name changes; every other class keeps its short name and only the root
 * prefix is swapped.
 */
final class RootNamespace
{
    public const OLD_ROOT = 'Piwik';
    public const NEW_ROOT = 'Matomo';
    public const OLD_PREFIX = 'Piwik\\';
    public const NEW_PREFIX = 'Matomo\\';
    public const FACADE_OLD = 'Piwik\\Piwik';
    public const FACADE_NEW = 'Matomo\\Matomo';

    /**
     * True when $name is exactly the facade FQCN (Piwik\Piwik), the one class
     * whose short name changes during the migration.
     */
    public static function isFacadeName(string $name): bool
    {
        return $name === self::FACADE_OLD;
    }
}
