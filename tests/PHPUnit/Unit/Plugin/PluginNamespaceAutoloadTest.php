<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Core\Plugin;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the dual plugin-namespace autoload layer introduced for the
 * `Piwik\` -> `Matomo\` root namespace migration: through Matomo 6.0 both the
 * legacy `Piwik\Plugins\` and the canonical `Matomo\Plugins\` plugin namespaces
 * autoload natively, whether the plugin lives in the standard plugins folder or
 * in a custom configured plugin directory. This lets plugin namespaces be
 * renamed batch by batch while every batch stays green.
 *
 * The custom plugin directory registered by the test bootstrap
 * ({@see \Piwik\Plugin\Manager::registerPluginDirAutoload}) is the only loader
 * that can resolve the fixture classes below, so a passing assertion here proves
 * the plugin-dir autoloader accepted the prefix.
 *
 * @group Core
 * @group Plugins
 */
class PluginNamespaceAutoloadTest extends TestCase
{
    /**
     * A `Matomo\Plugins\`-namespaced class autoloads natively (not via the
     * runtime alias) from a custom plugin directory.
     *
     * "Not via alias" is proven by the absence of a `Piwik\` counterpart: a
     * `Matomo\` class that resolved by aliasing a `Piwik\` class would leave that
     * `Piwik\` class defined, so asserting the counterpart is undefined proves
     * the `Matomo\` class loaded from its own file as the canonical class.
     */
    public function testMatomoPluginsNamespaceAutoloadsNatively()
    {
        $matomoClass = 'Matomo\\Plugins\\MatomoDirPlugin\\MatomoNativeClass';
        $piwikClass  = 'Piwik\\Plugins\\MatomoDirPlugin\\MatomoNativeClass';

        $this->assertTrue(class_exists($matomoClass), 'Matomo\\Plugins\\ class should autoload');

        $this->assertFalse(
            class_exists($piwikClass, false),
            'Piwik\\ counterpart must not be defined; the Matomo\\ class is canonical, not an alias'
        );
    }

    /**
     * A `Piwik\Plugins\`-namespaced class still autoloads natively (no regression)
     * from a custom plugin directory.
     */
    public function testPiwikPluginsNamespaceStillAutoloadsNatively()
    {
        $this->assertTrue(
            class_exists('Piwik\\Plugins\\CustomDirPlugin\\CustomClass'),
            'Piwik\\Plugins\\ class should still autoload'
        );
    }

    /**
     * A renamed plugin (`Matomo\Plugins\`) is reachable under the legacy
     * `Piwik\Plugins\` prefix too, via the runtime alias back-compat layer, so
     * un-migrated code referencing the old prefix keeps working through 6.0.
     */
    public function testRenamedPluginReachableUnderLegacyPiwikPrefixViaAlias()
    {
        $matomoClass = 'Matomo\\Plugins\\MatomoDirPlugin\\MatomoNativeClass';
        $piwikClass  = 'Piwik\\Plugins\\MatomoDirPlugin\\MatomoNativeClass';

        $this->assertTrue(class_exists($matomoClass), 'canonical Matomo\\ class must load first');

        $this->assertTrue(
            class_exists($piwikClass),
            'legacy Piwik\\ prefix should resolve to the renamed class via the alias layer'
        );

        $this->assertTrue(
            is_a($piwikClass, $matomoClass, true),
            'Piwik\\ alias should be assignable to the canonical Matomo\\ class'
        );
    }
}
