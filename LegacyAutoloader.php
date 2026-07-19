<?php

/**
 * Eager bidirectional alias autoloader for the `Piwik\` -> `Matomo\` root
 * namespace migration (Matomo 6.0).
 *
 * Matomo 6.0 renamed its PHP root namespace from `Piwik\` to `Matomo\`, while
 * shipping this back-compat alias layer so existing plugins and un-migrated
 * bundled submodules keep working through the 6.x release line. The alias layer
 * is removed in Matomo 7.0.
 *
 * Why eager aliasing. The previous design created `Piwik\X` -> `Matomo\X` aliases
 * lazily via the autoloader. PHP type-check positions — return types, param types,
 * `instanceof` with a variable class name, and `is_a()` — do NOT trigger
 * autoloading, so a lazily-created alias was never created for them and the check
 * failed by name comparison. Eager aliasing pre-creates the opposite-namespace
 * alias the moment a class is loaded, so any later type check against either
 * namespace name resolves to the same class entry.
 */

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace -- Bootstrap-level autoloader: loaded via composer `files` autoload and referenced as \LegacyAutoloader before namespace resolution is relevant. Removed together with the alias layer in Matomo 7.0.
class LegacyAutoloader
{
    /**
     * The facade is the one class whose short name changes during the migration:
     * `Piwik\Piwik` <-> `Matomo\Matomo`. Hardcoded so it is always in effect.
     */
    private const FACADE_PIWIK = 'Piwik\\Piwik';
    private const FACADE_MATOMO = 'Matomo\\Matomo';

    /**
     * Additional test-configurable exceptions (`Piwik\` class => `Matomo\` class)
     * for classes whose short name changes beyond the facade. Production needs only
     * the facade (handled by the constants above); this map exists so tests can
     * exercise the exceptions-map path with stand-in classes.
     *
     * @var array<string, string>
     */
    private static $exceptionMap = [];

    /**
     * Reverse of {@see $exceptionMap} (`Matomo\` class => `Piwik\` class), rebuilt
     * by {@see setExceptionMap()}.
     *
     * @var array<string, string>
     */
    private static $reverseExceptionMap = [];

    /**
     * Install the alias layer: make Composer's includes idempotent and prepend the
     * eager-aliasing loader. Composer `ClassLoader` references are not captured —
     * `load()` delegates file-based resolution to the rest of the live autoload
     * stack via {@see triggerRemainingLoaders()} (skipping self), which avoids the
     * re-entrant recursion that direct ClassLoader calls would cause.
     */
    public static function register()
    {
        self::patchComposerIncludeOnce();

        spl_autoload_register(array(self::class, 'load'), true, true);
    }

    /**
     * Eager-aliasing autoload entry point. Prepended so it runs first; it resolves
     * `Piwik\` / `Matomo\` classes by delegating to the rest of the stack, then
     * `class_alias`es the loaded class to its opposite-namespace name. Returns
     * truthy when it has handled the class (loaded and/or aliased) so the autoload
     * chain stops; returns false for classes outside the migrated namespaces so the
     * rest of the stack handles them normally.
     *
     * @param string $className The fully-qualified class name being autoloaded.
     * @return void The return value is ignored by `spl_autoload_*` (the chain
     *              continues until the class is defined); early `return` just exits
     *              this loader so the rest of the stack can run for unresolved names.
     */
    public static function load($className)
    {
        if (self::symbolExists($className, false)) {
            return;
        }

        $isMatomo = strncmp($className, 'Matomo\\', 7) === 0;
        $isPiwik = strncmp($className, 'Piwik\\', 6) === 0;

        if (!$isMatomo && !$isPiwik) {
            return;
        }

        $opposite = self::getOppositeName($className);
        $real = self::resolveAndAlias($className, $opposite);

        if ($real === null) {
            // Neither the class nor its opposite resolved; let the rest of the
            // stack (and the default spl fallback) try, so genuinely unknown
            // classes still error normally.
            return;
        }

        // Eagerly alias the opposite namespace so future type checks against it
        // resolve to the same class entry without triggering autoload.
        $oppositeOfReal = self::getOppositeName($real);
        if ($oppositeOfReal !== $real) {
            self::aliasIfMissing($real, $oppositeOfReal);
        }
    }

    /**
     * Returns the opposite-namespace name for a `Piwik\` or `Matomo\` class:
     * `Piwik\X` -> `Matomo\X` and `Matomo\X` -> `Piwik\X`, honouring the facade
     * (`Piwik\Piwik` <-> `Matomo\Matomo`) and the test-configurable exceptions
     * map. Names outside both namespaces are returned unchanged.
     *
     * This is a pure string operation that never triggers autoloading, so it is safe
     * to call from `load()` while resolving a class.
     *
     * @param string $class A fully-qualified class name.
     * @return string The opposite-namespace alias name, or $class unchanged if it is
     *                not in the `Piwik\` / `Matomo\` root namespace.
     */
    public static function getOppositeName(string $class): string
    {
        if ($class === self::FACADE_PIWIK) {
            return self::FACADE_MATOMO;
        }
        if ($class === self::FACADE_MATOMO) {
            return self::FACADE_PIWIK;
        }
        if (isset(self::$exceptionMap[$class])) {
            return self::$exceptionMap[$class];
        }
        if (isset(self::$reverseExceptionMap[$class])) {
            return self::$reverseExceptionMap[$class];
        }
        if (strncmp($class, 'Piwik\\', 6) === 0) {
            return 'Matomo\\' . substr($class, 6);
        }
        if (strncmp($class, 'Matomo\\', 7) === 0) {
            return 'Piwik\\' . substr($class, 7);
        }
        return $class;
    }

    /**
     * Configure additional exceptions (`Piwik\` class => `Matomo\` class) for
     * classes whose short name changes beyond the facade. Used in tests; the facade
     * mapping is always in effect via the constants above.
     *
     * @param array<string, string> $map
     */
    public static function setExceptionMap(array $map): void
    {
        self::$exceptionMap = $map;
        self::$reverseExceptionMap = [];
        foreach ($map as $piwik => $matomo) {
            self::$reverseExceptionMap[$matomo] = $piwik;
        }
    }

    /**
     * Reset the test-configurable exceptions map. The facade mapping is constant and
     * remains in effect. Intended as a test hook (phpunit does not back up static
     * attributes with `backupStaticAttributes="false"`).
     *
     */
    public static function reset(): void
    {
        self::$exceptionMap = [];
        self::$reverseExceptionMap = [];
    }

    /**
     * Resolve a class name by delegating to the rest of the autoload stack, then
     * alias it to its opposite namespace when the file declared the opposite name
     * (dual-root). Returns the real (declared) class name, or null if nothing
     * resolved.
     *
     * @param string $className The requested class name.
     * @param string $opposite Its opposite-namespace name (may equal $className).
     * @return string|null The real declared class name, or null if unresolved.
     */
    private static function resolveAndAlias($className, $opposite)
    {
        self::triggerRemainingLoaders($className);

        if (self::symbolExists($className, false)) {
            return $className;
        }

        if ($opposite !== $className && self::symbolExists($opposite, false)) {
            // Dual-root: a file declared the opposite-namespace class.
            self::aliasIfMissing($opposite, $className);
            return $opposite;
        }

        if ($opposite !== $className) {
            // The class name itself found no file; try loading the opposite name
            // (e.g. a `Piwik\` core reference with no `Piwik\` PSR-4 root).
            self::triggerRemainingLoaders($opposite);

            if (self::symbolExists($opposite, false)) {
                self::aliasIfMissing($opposite, $className);
                return $opposite;
            }

            if (self::symbolExists($className, false)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Invoke every autoload function registered in the stack except this loader,
     * stopping as soon as $className is defined. Skipping self prevents the
     * re-entrant recursion that would otherwise happen when `load()` triggers a
     * sub-load of the opposite-namespace name.
     *
     * @param string $className The class name to resolve via the rest of the stack.
     * @return void
     */
    private static function triggerRemainingLoaders($className)
    {
        foreach (spl_autoload_functions() as $autoloadFunction) {
            if (self::isSelfLoader($autoloadFunction)) {
                continue;
            }

            call_user_func($autoloadFunction, $className);

            if (self::symbolExists($className, false)) {
                return;
            }
        }
    }

    /**
     * Alias $alias to $target if $alias does not already exist and $target does.
     * The `@` suppresses the (benign) error if a concurrent resolution already
     * created the alias.
     *
     * @param string $target The real, already-declared class name.
     * @param string $alias The opposite-namespace name to alias to it.
     * @return void
     */
    private static function aliasIfMissing($target, $alias)
    {
        if (!self::symbolExists($alias, false) && self::symbolExists($target, false)) {
            @class_alias($target, $alias);
        }
    }

    /**
     * Returns true if a class, interface, or trait named $name is declared. When
     * $autoload is true, autoloading is triggered exactly once (via `class_exists`)
     * so interfaces and traits are also loaded.
     *
     * @param string $name
     * @param bool $autoload
     * @return bool
     */
    private static function symbolExists($name, $autoload)
    {
        if ($autoload) {
            return class_exists($name, true) || interface_exists($name, false) || trait_exists($name, false);
        }

        return class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false);
    }

    /**
     * @param mixed $autoloadFunction
     * @return bool
     */
    private static function isSelfLoader($autoloadFunction)
    {
        return is_array($autoloadFunction)
            && count($autoloadFunction) === 2
            && is_string($autoloadFunction[0])
            && $autoloadFunction[0] === self::class
            && is_string($autoloadFunction[1])
            && $autoloadFunction[1] === 'load';
    }

    /**
     * @param object $loader
     * @return bool
     */
    private static function isClassLoader($loader)
    {
        $loaderClass = get_class($loader);

        return $loaderClass === 'Composer\\Autoload\\ClassLoader'
            || substr($loaderClass, -29) === '\\Composer\\Autoload\\ClassLoader';
    }

    /**
     * Swap every registered Composer `ClassLoader`'s scope-isolated `include`
     * closure for an `include_once` variant.
     *
     * Through 6.0 both `Piwik\Plugins\` and `Matomo\Plugins\` PSR-4 roots point at
     * `plugins/`, so one plugin file is reachable under two class names.
     * `ClassLoader::loadClass()` includes the resolved file via `include` (not
     * `include_once`), so resolving the class under both prefixes could re-include
     * the file and redeclare the class (`Cannot declare class ...`). `include_once`
     * makes a repeat include a no-op. This is defense-in-depth: the eager alias
     * normally short-circuits the second resolution before any re-include, but the
     * patch keeps the include idempotent if it ever does occur. It only affects
     * composer's own include closure and is safe for class autoloading (a class
     * file is idempotent and should load at most once).
     */
    public static function patchComposerIncludeOnce()
    {
        $patchedLoaders = array();
        foreach (spl_autoload_functions() as $autoloadFunction) {
            if (!is_array($autoloadFunction) || !is_object($autoloadFunction[0])) {
                continue;
            }
            $loaderClass = get_class($autoloadFunction[0]);
            if (!self::isClassLoader($autoloadFunction[0])) {
                continue;
            }
            if (isset($patchedLoaders[$loaderClass]) || !class_exists($loaderClass)) {
                continue;
            }
            $patchedLoaders[$loaderClass] = true;
            try {
                $reflectionProperty = new \ReflectionProperty($loaderClass, 'includeFile');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue(
                    null,
                    \Closure::bind(static function ($file): void {
                        include_once $file;
                    }, null, null)
                );
            } catch (\Throwable $e) {
                // If the closure cannot be replaced for a given loader, leave it;
                // the project ClassLoader patch is the load-bearing one.
            }
        }
    }
}

LegacyAutoloader::register();
