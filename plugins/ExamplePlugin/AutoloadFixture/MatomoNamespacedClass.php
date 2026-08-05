<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\ExamplePlugin\AutoloadFixture;

/**
 * Test fixture for the dual plugin-namespace autoload roots.
 *
 * This class intentionally lives under the canonical `Matomo\Plugins\` namespace while
 * the rest of ExamplePlugin still uses the legacy `Matomo\Plugins\` namespace. It lets the
 * autoload tests assert that a renamed plugin class under the standard `plugins/` folder
 * is resolved natively by the `Matomo\Plugins\` PSR-4 root instead of falling back to the
 * `Piwik\` alias layer.
 */
class MatomoNamespacedClass
{
}
