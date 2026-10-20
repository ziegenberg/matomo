<?php

/**
 * @param \Piwik\Access $access
 * @return Piwik\DataTable
 * @throws Piwik\Exception\UnexpectedWebsiteFound
 * @var array<string, \Piwik\Plugin\ProcessedMetric>
 * @method static Piwik\Archive getInstance()
 * @see \Piwik\Archive::build()
 * @see \Piwik\Piwik::getInstance()
 * {@link \Piwik\Access::getInstance()}
 * {@link Piwik\DataTable DataTable}
 * @phpstan-require-implements \Piwik\Plugin\Report
 * @phpstan-import-type SegmentType from \Piwik\Plugins\SegmentEditor\API
 * @param array<class-string<\Piwik\Settings\Storage\Backend>> $backends
 * @param callable(function (\Piwik\DataTable $table): void): void $cb
 */
function foo() {}
-----
<?php

/**
 * @param \Matomo\Access $access
 * @return Matomo\DataTable
 * @throws Matomo\Exception\UnexpectedWebsiteFound
 * @var array<string, \Matomo\Plugin\ProcessedMetric>
 * @method static Matomo\Archive getInstance()
 * @see \Matomo\Archive::build()
 * @see \Matomo\Matomo::getInstance()
 * {@link \Matomo\Access::getInstance()}
 * {@link Matomo\DataTable DataTable}
 * @phpstan-require-implements \Matomo\Plugin\Report
 * @phpstan-import-type SegmentType from \Matomo\Plugins\SegmentEditor\API
 * @param array<class-string<\Matomo\Settings\Storage\Backend>> $backends
 * @param callable(function (\Matomo\DataTable $table): void): void $cb
 */
function foo() {}
