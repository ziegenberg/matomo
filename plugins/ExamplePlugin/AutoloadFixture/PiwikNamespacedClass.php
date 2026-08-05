<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ExamplePlugin\AutoloadFixture;

/**
 * Test fixture for the dual plugin-namespace autoload roots.
 *
 * This class intentionally lives under the legacy `Matomo\Plugins\` namespace
 * while the rest of ExamplePlugin has been migrated to `Matomo\Plugins\`. It lets
 * the autoload tests assert that an un-renamed plugin class under the standard
 * `plugins/` folder is still resolved natively by the `Matomo\Plugins\` PSR-4 root
 * (no regression from adding the `Matomo\Plugins\` root), instead of falling
 * back to the `Matomo\` alias layer.
 */
class PiwikNamespacedClass
{
}
