<?php

namespace Matomo\Plugins\FeatureFlags\config;

use Matomo\DI;
use Matomo\Log\Logger;
use Matomo\Plugins\FeatureFlags\FeatureFlagManager;

return [
    /**
     * The order of the these storage mechanisms for the feature flags determines how they cascade down.
     *
     * The first one will be overwritten by the second one (if set).
     */
    'featureflag.storages' => [
        DI::get('Matomo\Plugins\FeatureFlags\Storage\ConfigFeatureFlagStorage'),
    ],
    /**
     * Defines the directory that Plugin\Manager::getInstance()->findMultipleComponents will search for when loading feature flags
     *
     * Configurable here for testing purposes only
     */
    'featureflag.dir_of_feature_flags' => 'FeatureFlags',
    FeatureFlagManager::class => DI::autowire()
        ->constructor(DI::get('featureflag.storages'), DI::get(Logger::class)),
];
