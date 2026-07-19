<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\CustomDirPlugin;

/**
 * Test fixture for the dual plugin-namespace autoload roots in a custom plugin
 * directory. Used to assert that a `Matomo\Plugins\`-namespaced class installed outside
 * the standard plugins folder autoloads under both the `Piwik\Plugins\` and
 * `Matomo\Plugins\` prefixes.
 */
class MatomoCustomClass
{
}
