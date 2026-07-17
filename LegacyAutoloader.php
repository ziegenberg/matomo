<?php

class LegacyAutoloader
{
    /**
     * Maps a `Piwik\...` class to a `Matomo\...` class whose short name differs from the
     * prefix-swapped form (e.g. the facade `Piwik\Piwik` => `Matomo\Matomo`). Consulted
     * before the default prefix swap so such classes alias correctly.
     *
     * @var array<string, string>
     */
    private static $piwikToMatomoExceptions = [];

    /**
     * Deprecation events recorded when a `Piwik\` name is resolved to its `Matomo\`
     * counterpart. One entry per distinct class per request: PHP only invokes the
     * autoloader once per class, and `class_alias` caches the result.
     *
     * @var array<int, array{piwik: string, matomo: string, plugin: string|null}>
     */
    private static $deprecations = [];

    /**
     * Optional sink that receives each deprecation event as it is recorded. When set to
     * a callable, the callable is invoked with the event array. The default value is null
     * (no sink); production wires a sink that routes to the PSR-3 logger.
     *
     * @var callable|null
     */
    private static $deprecationSink;

    /**
     * Optional resolver that returns the current plugin directory roots, used to attribute
     * a deprecation to the calling plugin. Default null: attribution is skipped and the
     * recorded event's `plugin` is null. Production wires this to
     * `\Piwik\Plugin\Manager::getPluginsDirectories()` (one line) once the container is up.
     *
     * @var callable|null
     */
    private static $pluginRootsResolver;

    public function __construct()
    {
        spl_autoload_register(array($this, 'load_class'));
    }

    public static function register()
    {
        new LegacyAutoloader();
    }

    /**
     * @param array<string, string> $map `Piwik\...` class => `Matomo\...` class
     */
    public static function setPiwikToMatomoExceptions(array $map): void
    {
        self::$piwikToMatomoExceptions = $map;
    }

    /**
     * @param callable|null $sink
     */
    public static function setDeprecationSink($sink): void
    {
        self::$deprecationSink = $sink;
    }

    /**
     * @param callable|null $resolver Returns string[] of plugin directory roots.
     */
    public static function setPluginRootsResolver($resolver): void
    {
        self::$pluginRootsResolver = $resolver;
    }

    /**
     * @return array<int, array{piwik: string, matomo: string, plugin: string|null}>
     */
    public static function getRecordedDeprecations(): array
    {
        return self::$deprecations;
    }

    public static function clearRecordedDeprecations(): void
    {
        self::$deprecations = [];
    }

    /**
     * Attributes a deprecation to the originating plugin by finding the first backtrace
     * frame whose file lives under a registered plugin root. The directory segment
     * immediately following the root is the plugin name. Returns null when the call
     * originates outside any plugin (i.e. core).
     *
     * Pure and side-effect-free so it can be unit-tested in isolation.
     *
     * @param array<int, array> $trace      A debug_backtrace() result.
     * @param array<int|string, string> $pluginRoots Absolute plugin directory paths
     *                                               (trailing slash optional).
     * @return string|null
     */
    public static function pluginFromBacktrace(array $trace, array $pluginRoots)
    {
        $normalizedRoots = array();
        foreach ($pluginRoots as $root) {
            if ($root === '') {
                continue;
            }
            $normalizedRoots[] = rtrim(str_replace('\\', '/', $root), '/') . '/';
        }

        foreach ($trace as $frame) {
            if (empty($frame['file']) || !is_string($frame['file'])) {
                continue;
            }
            $file = str_replace('\\', '/', $frame['file']);

            foreach ($normalizedRoots as $root) {
                if (strpos($file, $root) !== 0) {
                    continue;
                }
                $relative = substr($file, strlen($root));
                $segments = explode('/', $relative);
                $plugin = $segments[0];
                if ($plugin !== '') {
                    return $plugin;
                }
            }
        }

        return null;
    }

    public function load_class($className)
    {
        if (strpos($className, 'Matomo\\') === 0) {
            $newName = 'Piwik' . substr($className, 6);
            if (class_exists($newName) && !class_exists($className, false)) {
                @class_alias($newName, $className);
            }
        } elseif (strpos($className, 'Piwik\\') === 0) {
            $newName = self::$piwikToMatomoExceptions[$className]
                ?? ('Matomo' . substr($className, 5));
            if (class_exists($newName) && !class_exists($className, false)) {
                @class_alias($newName, $className);
                $this->recordDeprecation($className, $newName);
            }
        }
    }

    /**
     * Records a `Piwik\` namespace deprecation. Suppressed entirely in tracker requests
     * so the tracker hot path incurs no recording cost; the alias still resolved above for
     * correctness.
     *
     * @param string $piwikClass
     * @param string $matomoClass
     */
    private function recordDeprecation($piwikClass, $matomoClass): void
    {
        if (!empty($GLOBALS['PIWIK_TRACKER_MODE'])) {
            return;
        }

        $event = array(
            'piwik'  => $piwikClass,
            'matomo' => $matomoClass,
            'plugin' => null,
        );

        if (self::$pluginRootsResolver !== null) {
            $roots = call_user_func(self::$pluginRootsResolver);
            if (is_array($roots)) {
                $event['plugin'] = self::pluginFromBacktrace(
                    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
                    $roots
                );
            }
        }

        self::$deprecations[] = $event;

        if (self::$deprecationSink !== null) {
            call_user_func(self::$deprecationSink, $event);
        } else {
            self::emitDefaultNotice($event);
        }
    }

    /**
     * Default sink: lazily routes a deprecation to the PSR-3 logger once the container is up.
     *
     * `class_exists(..., false)` never triggers autoloading, so this never re-enters the
     * autoloader from within itself. Before the container exists the call is a no-op; in
     * tracker requests `recordDeprecation` returns before reaching here.
     *
     * @param array{piwik: string, matomo: string, plugin: string|null} $event
     */
    private static function emitDefaultNotice(array $event): void
    {
        if (!class_exists('Piwik\\Container\\StaticContainer', false)) {
            return;
        }
        try {
            $logger = \Piwik\Container\StaticContainer::get('Piwik\\Log\\LoggerInterface');
        } catch (\Throwable $e) {
            return;
        }
        $logger->notice(
            'Deprecated Piwik\\ namespace use of {piwik}; use {matomo} instead (plugin: {plugin}).',
            $event
        );
    }
}

LegacyAutoloader::register();
