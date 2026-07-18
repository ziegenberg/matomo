<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\String_\RenameStringRector;
use Utils\Rector\NamespaceMap\NamespaceMap;
use Utils\Rector\Rector\RenameRootNamespaceRector;

// Mechanical rename of the root namespace Piwik\ -> Matomo\ (including the
// facade Piwik\Piwik -> Matomo\Matomo), reusable by core and every submodule
// repo.
//
// The generated Piwik\* -> Matomo\* class map drives the built-in rules:
// RenameClassRector covers use statements, FQCN Name nodes, extends/implements,
// PHPDoc, and (with withImportNames) shortens FQCNs to the renamed imports and
// resolves unqualified references such as `Piwik::` static calls; RenameStringRector
// covers static class-name strings. The custom RenameRootNamespaceRector covers
// the three forms the built-ins cannot reach: namespace declarations (RenameClassRector
// returns null for a plain Name node), the facade's declared class name
// (RenameClassRector::refactorClassLike renames only implements), and the dynamic
// string forms (sprintf / interpolated templates, prefix fragments, prefix sentinels).
//
// withImportNames is required for correctness, not style: it is what resolves an
// unqualified `Piwik::` static call (via the renamed `use Matomo\Matomo;` import)
// to `Matomo::`. withPhpSets is intentionally NOT enabled so the rename stays
// mechanical — no strpos -> str_starts_with, no string -> ::class, no other
// version modernisation churn.
//
// The map is generated from this repo's own Composer PSR-4 roots, so the same
// config renames core and any submodule without per-repo edits. The LegacyAutoloader
// (the alias layer) and fixtures that intentionally exercise the Piwik\ alias or
// the dual autoload roots are skipped so the rename does not undo them.
$loader = require __DIR__ . '/vendor/autoload.php';
$namespaceMap = NamespaceMap::fromClassLoader($loader);
$classMap = $namespaceMap->toArray();

// Piwik\Manifest is a release-generated class (config/manifest.inc.php, stubbed in
// bootstrap-phpstan.php) that is not under a PSR-4 root, so it is absent from the
// generated map. Add it explicitly so core/FileIntegrity.php's string
// (class_exists('Piwik\Manifest')) and Name-node (\Piwik\Manifest::$files)
// references rename consistently to Matomo\Manifest. The release build that emits
// manifest.inc.php must declare Matomo\Manifest to match.
$classMap['Piwik\\Manifest'] = 'Matomo\\Manifest';

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/core',
        __DIR__ . '/misc',
        __DIR__ . '/plugins',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Bundled submodule plugins migrate per-repo through the 6.x cycle, not
        // lockstep with core. They are skipped here and renamed in their own
        // repos with this same config.
        __DIR__ . '/plugins/SecurityInfo/',
        __DIR__ . '/plugins/TreemapVisualization/',
        __DIR__ . '/plugins/VisitorGenerator/',
        __DIR__ . '/plugins/CustomAlerts/',
        __DIR__ . '/plugins/TasksTimetable/',
        __DIR__ . '/plugins/QueuedTracking/',
        __DIR__ . '/plugins/LogViewer/',
        __DIR__ . '/plugins/AnonymousPiwikUsageMeasurement/',
        __DIR__ . '/plugins/Bandwidth/',
        __DIR__ . '/plugins/LoginLdap/',
        __DIR__ . '/plugins/MarketingCampaignsReporting/',
        __DIR__ . '/plugins/TagManager/',
        __DIR__ . '/plugins/DeviceDetectorCache/',
        __DIR__ . '/plugins/Provider/',
        __DIR__ . '/plugins/CustomVariables/',
        __DIR__ . '/plugins/TrackingSpamPrevention/',
        // Separate tool, not Matomo-namespaced PHP.
        __DIR__ . '/misc/log-analytics/',
        // Static icon assets, no PHP.
        __DIR__ . '/plugins/Morpheus/icons/',
        // The alias layer itself: it must keep its Piwik\ references (it is what
        // aliases them). Loaded for autoloading below, but not renamed.
        __DIR__ . '/LegacyAutoloader.php',
        // This config.
        __DIR__ . '/rector.php',
        // Fixtures that intentionally exercise the Piwik\ alias or the dual
        // Piwik\Plugins\ / Matomo\Plugins\ autoload roots, or simulate legacy
        // plugin code. Renaming them would undo what they test.
        __DIR__ . '/tests/resources/LegacyAutoloader/',
        __DIR__ . '/tests/resources/LegacyAutoloaderDeprecationFixtures.php',
        __DIR__ . '/tests/resources/custompluginsdir/',
        __DIR__ . '/tests/resources/Updater/testpluginUpdates/',
    ])
    ->withAutoloadPaths([
        __DIR__ . '/LegacyAutoloader.php',
    ])
    ->withRootFiles()
    ->withImportNames(removeUnusedImports: true)
    ->withConfiguredRule(RenameClassRector::class, $classMap)
    ->withConfiguredRule(RenameStringRector::class, $classMap)
    ->withConfiguredRule(RenameRootNamespaceRector::class, $classMap);
