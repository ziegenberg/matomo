<?php

use Matomo\Cache\Eager;
use Matomo\SettingsServer;

return [

    'path.root' => PIWIK_DOCUMENT_ROOT,

    'path.misc.user' => 'misc/user/',

    'path.tmp' => function (\Matomo\Container\Container $c) {
        $root = PIWIK_USER_PATH;

        // TODO remove that special case and instead have plugins override 'path.tmp' to add the instance id
        if ($c->has('ini.General.instance_id')) {
            $instanceId = $c->get('ini.General.instance_id');
            $instanceId = $instanceId ? '/' . $instanceId : '';
        } else {
            $instanceId = '';
        }

        /** @var Matomo\Config\ $config */
        $config = $c->get('Matomo\Config');
        $general = $config->General;
        $tmp = empty($general['tmp_path']) ? '/tmp' : $general['tmp_path'];

        return $root . $tmp . $instanceId;
    },

    'path.tmp.templates' => Matomo\DI::string('{path.tmp}/templates_c'),

    'path.cache' => Matomo\DI::string('{path.tmp}/cache/tracker/'),

    'view.clearcompiledtemplates.enable' => true,

    'twig.cache' => Matomo\DI::string('{path.tmp.templates}'),

    'Matomo\Cache\Eager' => function (\Matomo\Container\Container $c) {
        $backend = $c->get('Matomo\Cache\Backend');
        $cacheId = $c->get('cache.eager.cache_id');

        if (SettingsServer::isTrackerApiRequest()) {
            $eventToPersist = 'Tracker.end';
            $cacheId .= 'tracker';
        } else {
            $eventToPersist = 'Request.dispatch.end';
            $cacheId .= 'ui';
        }

        $cache = new Eager($backend, $cacheId);
        \Matomo\Matomo::addAction($eventToPersist, function () use ($cache) {
            $cache->persistCacheIfNeeded(43200);
        });

        return $cache;
    },
    'Matomo\Cache\Backend' => function (\Matomo\Container\Container $c) {
        // If Piwik is not installed yet, it's possible the tmp/ folder is not writable
        // we prevent failing with an unclear message eg. coming from doctrine-cache
        // by forcing to use a cache backend which always works ie. array
        if (!\Matomo\SettingsPiwik::isMatomoInstalled()) {
            $backend = 'array';
        } else {
            try {
                $backend = $c->get('ini.Cache.backend');
            } catch (\Matomo\Exception\DI\NotFoundException $ex) {
                $backend = 'chained'; // happens if global.ini.php is not available
            }
        }

        return \Matomo\Cache::buildBackend($backend);
    },
    'cache.eager.cache_id' => function () {
        return 'eagercache-' . str_replace(['.', '-'], '', \Matomo\Version::VERSION) . '-';
    },

    /**
     * A list of API query parameters that map to entity IDs, for example, `idGoal` for goals.
     *
     * If your plugin introduces new entities that can be fetched or manipulated by ID through
     * API requests, you should add the query parameters that represent the new entity's IDs
     * to this array.
     */
    'entities.idNames' => Matomo\DI::add(['idGoal', 'idDimension']),

    /**
     * If your plugin uses custom query parameters in API requests (that is, query parameters not used
     * by a core plugin), and you want to be able to use those query parameters in system tests, you
     * will need to add them, via DI, to this array. Otherwise, in system tests, they will be
     * silently ignored.
     *
     * Note: if the query parameter has been added to `'entities.idNames'`, it does not need to be added
     * here as well.
     */
    'DocumentationGenerator.customParameters' => [],

    /**
     * A list of exact (case-sensitive) `Module.Action` pairs where token_auths with write/admin
     * access are allowed for non-API requests.
     *
     * Plugins can register actions in their `config/config.php`:
     *
     * return [
     *     'token_auth.write_admin_allowed_module_actions' => \Matomo\DI::add(['MyPlugin.myAction']),
     * ];
     *
     * @internal
     */
    'token_auth.write_admin_allowed_module_actions' => [],

    \Matomo\Log\Logger::class => Matomo\DI::create(\Matomo\Log\NullLogger::class),
    \Matomo\Log\LoggerInterface::class => Matomo\DI::create(\Matomo\Log\NullLogger::class),

    'Matomo\Translation\Loader\LoaderInterface' => Matomo\DI::autowire('Matomo\Translation\Loader\LoaderCache')
        ->constructorParameter('loader', Matomo\DI::get('Matomo\Translation\Loader\JsonFileLoader')),

    'DeviceDetector\Cache\Cache' => Matomo\DI::autowire('Matomo\DeviceDetector\DeviceDetectorCache')->constructor(86400),

    // specify plugins to load on demand via DI config. mostly for tests.
    'plugins.shouldLoadOnDemand' => [],

    // allow users to override plugin hardcoded value and avoid loading on demand
    'plugins.shouldNotLoadOnDemand' => [],

    'observers.global' => [],

    'dev.forced_plugin_update_result' => null,

    /**
     * By setting this option to false, the check that the DB schema version matches the version of the source code will
     * be no longer performed. Thus it allows you to execute for example a newer version of Matomo with an older Matomo
     * database version. Please note disabling this setting is not recommended because often an older DB version is not
     * compatible with newer source code.
     * If you disable this setting, make sure to execute the updates after updating the source code. The setting can be
     * useful if you want to update Matomo without any outage when you know the current source code update will still
     * run fine for a short time while in the background the database updates are running.
     */
    'EnableDbVersionCheck' => true,

    'fileintegrity.ignore' => Matomo\DI::add([
        '*.htaccess',
        '*web.config',
        'bootstrap.php',
        'favicon.ico',
        'robots.txt',
        '.bowerrc',
        '.lfsconfig',
        '.phpstorm.meta.php',
        'config/config.ini.php',
        'config/config.php',
        'config/common.ini.php',
        'config/*.config.ini.php',
        'config/manifest.inc.php',
        'misc/*.dat',
        'misc/*.dat.gz',
        'misc/*.mmdb',
        'misc/*.mmdb.gz',
        'misc/*.bin',
        'misc/user/*png',
        'misc/user/*svg',
        'misc/user/*js',
        'misc/user/*/config.ini.php',
        'misc/package',
        'misc/package/WebAppGallery/*.xml',
        'misc/package/WebAppGallery/install.sql',
        'plugins/ImageGraph/fonts/unifont.ttf',
        'plugins/*/config/tracker.php',
        'plugins/*/config/config.php',
        'vendor/autoload.php',
        'vendor/composer/autoload_real.php',
        'vendor/szymach/c-pchart/app/*',
        'tmp/*',
        // Search engine sites verification
        'google*.html',
        'BingSiteAuth.xml',
        'yandex*.html',
        // common files on shared hosters
        'php.ini',
        '.user.ini',
        'error_log',
        // Files below are not expected but they used to be present in older Piwik versions and may be still here
        // As they are not going to cause any trouble we won't report them as 'File to delete'
        '*.coveralls.yml',
        '*.scrutinizer.yml',
        '*.gitignore',
        '*.gitkeep',
        '*.gitmodules',
        '*.gitattributes',
        '*.git-blame-ignore-revs',
        '*.bower.json',
        '*.travis.yml',
    ]),

    'Matomo\EventDispatcher' => Matomo\DI::autowire()->constructorParameter('observers', Matomo\DI::get('observers.global')),

    'login.allowlist.ips' => function (\Matomo\Container\Container $c) {
        /** @var Matomo\Config\ $config */
        $config = $c->get('Matomo\Config');
        $general = $config->General;

        $ips = [];
        if (!empty($general['login_allowlist_ip']) && is_array($general['login_allowlist_ip'])) {
            $ips = $general['login_allowlist_ip'];
        } elseif (!empty($general['login_whitelist_ip']) && is_array($general['login_whitelist_ip'])) {
            // for BC
            $ips = $general['login_whitelist_ip'];
        }

        $ipsResolved = [];

        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP) || \Matomo\Network\IPUtils::getIPRangeBounds($ip) !== null) {
                $ipsResolved[] = $ip;
            } else {
                $lazyCache = \Matomo\Cache::getLazyCache();
                $cacheKey = 'DNS.' . md5($ip);

                $resolvedIps = $lazyCache->fetch($cacheKey);

                if (!is_array($resolvedIps)) {
                    $resolvedIps = [];

                    $ipFromHost = @gethostbyname($ip);
                    if (!empty($ipFromHost) && $ipFromHost !== $ip) {
                        $resolvedIps[] = $ipFromHost;
                    }

                    if (function_exists('dns_get_record')) {
                        $entry = @dns_get_record($ip, DNS_AAAA);

                        if (
                            !empty($entry['0']['ipv6'])
                            && filter_var($entry['0']['ipv6'], FILTER_VALIDATE_IP)
                        ) {
                            $resolvedIps[] = $entry['0']['ipv6'];
                        }
                    }

                    $lazyCache->save($cacheKey, $resolvedIps, 30);
                }

                $ipsResolved = array_merge($ipsResolved, $resolvedIps);
            }
        }

        return $ipsResolved;
    },

    /**
     * This defines a list of hostnames Matomo's Http class will deny requests to. Wildcards (*) can be used in the
     * beginning to match any subdomain level or in the end to match any tlds
     */
    'http.blocklist.hosts' => [
        '*.amazonaws.com',
    ],

    'Matomo\Tracker\VisitorRecognizer' => Matomo\DI::autowire()
        ->constructorParameter('trustCookiesOnly', Matomo\DI::get('ini.Tracker.trust_visitors_cookies'))
        ->constructorParameter('visitStandardLength', Matomo\DI::get('ini.Tracker.visit_standard_length'))
        ->constructorParameter('lookbackNSecondsCustom', Matomo\DI::get('ini.Tracker.window_look_back_for_visitor')),

    'Matomo\Tracker\Settings' => Matomo\DI::autowire()
        ->constructorParameter(
            'isSameFingerprintsAcrossWebsites',
            Matomo\DI::get('ini.Tracker.enable_fingerprinting_across_websites')
        ),

    'archiving.performance.logger' => null,

    \Matomo\CronArchive\Performance\Logger::class => Matomo\DI::autowire()
        ->constructorParameter('logger', Matomo\DI::get('archiving.performance.logger')),

    \Matomo\Concurrency\LockBackend::class => \Matomo\DI::get(\Matomo\Concurrency\LockBackend\MySqlLockBackend::class),

    \Matomo\Segment\SegmentsList::class => function () {
        return \Matomo\Segment\SegmentsList::get();
    }
];
