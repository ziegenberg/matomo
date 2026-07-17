<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Utils\Rector\Rector\RenameRootNamespaceRector;

// Isolated test of the custom RenameRootNamespaceRector. The small map stands
// in for the generated Piwik\* -> Matomo\* class map: its keys are the exact
// class-name strings the rule must SKIP (so RenameStringRector rewrites them,
// including the facade Piwik\Piwik -> Matomo\Matomo short-name change).

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameRootNamespaceRector::class, [
        'Piwik\\Plugins\\RealClass\\API' => 'Matomo\\Plugins\\RealClass\\API',
        'Piwik\\Piwik' => 'Matomo\\Matomo',
    ]);
};
