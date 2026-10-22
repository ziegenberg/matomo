<?php

/**
 * Eager bidirectional alias autoloader for the `Piwik\` -> `Matomo\` root
 * namespace migration (Matomo 7.0).
 *
 * Matomo 7.0 renames its PHP root namespace from `Piwik\` to `Matomo\`, while
 * shipping this back-compat alias layer so existing plugins keep working through
 * the 7.x release line. The alias layer is removed in Matomo 8.0.
 *
 * Why eager aliasing. The previous design created `Piwik\X` -> `Matomo\X` aliases
 * lazily via the autoloader. PHP type-check positions — return types, param types,
 * `instanceof` with a variable class name, and `is_a()` — do NOT trigger
 * autoloading, so a lazily-created alias was never created for them and the check
 * failed by name comparison. Eager aliasing pre-creates the opposite-namespace
 * alias the moment a class is loaded, so any later type check against either
 * namespace name resolves to the same class entry.
 *
 * Mechanism. A single loader is prepended to the autoload stack and owns all
 * `Piwik\` / `Matomo\` resolution. For each requested class it delegates to the
 * rest of the stack (Composer's `ClassLoader` for the PSR-4 roots under `core/`
 * and `plugins/`, and `Plugin\Manager`'s custom plugin-directory loader for
 * plugins outside `plugins/`), then `class_alias`es the loaded class to its
 * opposite-namespace name — honouring the facade exception (`Piwik\Piwik` <->
 * `Matomo\Matomo`). This covers the standard-dir native load, the custom-dir
 * load, and the dual-root case where one plugin file is reachable under both
 * `Piwik\Plugins\` and `Matomo\Plugins\` (the file declares one namespace; the
 * other is bridged by an alias).
 *
 * A bootstrap catch-up pass aliases classes already declared when the loader
 * registers, so the alias set no longer depends on load order: every declared
 * `Matomo\` class gets its `Piwik\` counterpart immediately, including classes
 * loaded before the autoloader itself (e.g. under PHPUnit's
 * `@runInSeparateProcess`).
 *
 * The alias-name resolution (facade exception + `Piwik\`/`Matomo\` prefix swap)
 * lives in this class — in the root namespace, always loaded — so `load()` never
 * triggers autoloading of a helper class while resolving a class.
 *
 * Dual-root re-include. Through 7.0 both `Piwik\Plugins\` and `Matomo\Plugins\`
 * PSR-4 roots point at `plugins/`, so one file is reachable under two class names.
 * Composer's `ClassLoader` includes files with `include` (not `include_once`),
 * so resolving the class under both prefixes could re-include the file and
 * redeclare it. `patchComposerIncludeOnce()` swaps the loader's include closure
 * for an `include_once` variant as defense-in-depth; the eager alias normally
 * short-circuits the second resolution before any re-include, but the patch keeps
 * the include idempotent if it ever does occur.
 */

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace -- Bootstrap-level autoloader: loaded via composer `files` autoload and referenced as \LegacyAutoloader (e.g. `instanceof` in rector.php) before namespace resolution is relevant. Removed together with the alias layer in Matomo 8.0.
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
     * Install the alias layer: make Composer's includes idempotent, prepend the
     * eager-aliasing loader, then run the bootstrap catch-up pass so classes
     * already declared get their opposite-namespace alias immediately. Composer
     * `ClassLoader` references are not captured — `load()` delegates file-based
     * resolution to the rest of the live autoload stack via
     * {@see triggerRemainingLoaders()} (skipping self), which avoids the
     * re-entrant recursion that direct ClassLoader calls would cause.
     */
    public static function register()
    {
        self::patchComposerIncludeOnce();

        spl_autoload_register(array(self::class, 'load'), true, true);

        self::catchUpDeclaredClasses();
    }

    /**
     * Eager-aliasing autoload entry point. Prepended so it runs first; it resolves
     * `Piwik\` / `Matomo\` classes by delegating to the rest of the stack, then
     * `class_alias`es the loaded class to its opposite-namespace name. Names
     * outside both namespaces are ignored so the rest of the stack handles them
     * normally.
     *
     * The requested class being already declared is not an early return: falling
     * through means a class that was manually required under its own name (no
     * autoload involved) still gets its opposite-namespace alias created by the
     * eager-aliasing step below.
     *
     * @param string $className The fully-qualified class name being autoloaded.
     * @return void The return value is ignored by `spl_autoload_*` (the chain
     *              continues until the class is defined); early `return` just exits
     *              this loader so the rest of the stack can run for unresolved names.
     */
    public static function load($className)
    {
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
     * Alias every `Piwik\` / `Matomo\` class already declared to its
     * opposite-namespace name. Run after {@see register()}, this makes the alias
     * set independent of load order: classes required before the autoloader
     * registered still get their counterpart alias, so later type checks against
     * either name resolve to the same class entry.
     *
     * Idempotent and safe to call again (aliases are created only when missing).
     */
    public static function catchUpDeclaredClasses(): void
    {
        $declared = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());

        foreach ($declared as $name) {
            if (strncmp($name, 'Piwik\\', 6) === 0 || strncmp($name, 'Matomo\\', 7) === 0) {
                $opposite = self::getOppositeName($name);
                if ($opposite !== $name) {
                    self::aliasIfMissing($name, $opposite);
                }
            }
        }
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
        if (self::symbolExists($className, false)) {
            return $className;
        }

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
     * so interfaces and traits are also loaded. `class_exists` also covers enums
     * (they are final classes).
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
     * Through 7.0 both `Piwik\Plugins\` and `Matomo\Plugins\` PSR-4 roots point at
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
