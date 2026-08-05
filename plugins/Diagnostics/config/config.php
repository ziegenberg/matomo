<?php

use Matomo\Plugins\Diagnostics\Diagnostic\CronArchivingLastRunCheck;
use Matomo\Plugins\Diagnostics\Diagnostic\DeprecatedNamespaceUsageInformational;
use Matomo\Plugins\Diagnostics\Diagnostic\RequiredPrivateDirectories;
use Matomo\Plugins\Diagnostics\Diagnostic\RecommendedPrivateDirectories;

return array(
    // Diagnostics for everything that is required for Piwik to run
    'diagnostics.required' => array(
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PhpVersionCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DbAdapterCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DbReaderCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PhpExtensionsCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PhpFunctionsCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PhpSettingsCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\WriteAccessCheck'),
    ),
    // Diagnostics for recommended features
    'diagnostics.optional' => array(
        Matomo\DI::get(RequiredPrivateDirectories::class),
        Matomo\DI::get(RecommendedPrivateDirectories::class),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\FileIntegrityCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PHPBinaryCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\TrackerCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\MemoryLimitCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\TimezoneCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\HttpClientCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PageSpeedCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\GdExtensionCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\RecommendedExtensionsCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\RecommendedFunctionsCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\NfsDiskCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\CronArchivingCheck'),
        Matomo\DI::get(CronArchivingLastRunCheck::class),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DatabaseAbilitiesCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DbOverSSLCheck'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DbMaxPacket'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\ForceSSLCheck'),
    ),
    'diagnostics.informational' => array(
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\MatomoInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\PhpInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\DatabaseInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\ConfigInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\ServerInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\ReportInformational'),
        Matomo\DI::get('Matomo\Plugins\Diagnostics\Diagnostic\UserInformational'),
        Matomo\DI::get(\Matomo\Plugins\Diagnostics\Diagnostic\ArchiveInvalidationsInformational::class),
        Matomo\DI::get(DeprecatedNamespaceUsageInformational::class),
    ),
    // Allows other plugins to disable diagnostics that were previously registered
    'diagnostics.disabled' => array(),

    'Matomo\Plugins\Diagnostics\DiagnosticService' => Matomo\DI::autowire()
        ->constructor(Matomo\DI::get('diagnostics.required'), Matomo\DI::get('diagnostics.optional'), Matomo\DI::get('diagnostics.informational'), Matomo\DI::get('diagnostics.disabled')),

    'Matomo\Plugins\Diagnostics\Diagnostic\MemoryLimitCheck' => Matomo\DI::autowire()
        ->constructorParameter('minimumMemoryLimit', Matomo\DI::get('ini.General.minimum_memory_limit')),

    'Matomo\Plugins\Diagnostics\Diagnostic\WriteAccessCheck' => Matomo\DI::autowire()
        ->constructorParameter('tmpPath', Matomo\DI::get('path.tmp')),
);
