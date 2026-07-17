<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\MatomoDirPlugin;

/**
 * Stand-in for a plugin whose namespace has been renamed to the canonical
 * `Matomo\Plugins\` root. Lives in the custom plugin directory used by the test
 * suite so the plugin-dir autoloader ({@see \Piwik\Plugin\Manager::registerPluginDirAutoload})
 * is the only loader that can resolve it. The folder intentionally omits a
 * `MatomoDirPlugin.php` and `plugin.json` so the plugin scanner
 * ({@see \Piwik\Plugin\Manager::pluginStructureLooksValid}) skips it.
 */
class MatomoNativeClass
{
    // autoload behaviour is asserted externally; no behaviour here.
}
