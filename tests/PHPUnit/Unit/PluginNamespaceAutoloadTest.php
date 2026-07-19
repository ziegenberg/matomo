<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

/**
 * Verifies the dual plugin-namespace autoload roots introduced for the
 * `Piwik\` -> `Matomo\` root-namespace migration.
 *
 * Through the 6.x release line both `Piwik\Plugins\` and `Matomo\Plugins\` map to the
 * plugin directories so plugins keep loading natively while they are renamed
 * batch by batch. These tests assert the external autoload behaviour only: a
 * renamed plugin class resolves natively under `Matomo\Plugins\`, an un-renamed
 * class still resolves under `Piwik\Plugins\`, and a class installed in a custom
 * configured plugin directory resolves under either prefix.
 *
 * @group Core
 * @group PluginNamespaceAutoload
 */
class PluginNamespaceAutoloadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * A renamed plugin class (declared in `Matomo\Plugins\`) under the standard
     * plugins/ folder must autoload natively via the `Matomo\Plugins\` PSR-4 root,
     * not via the `Piwik\` alias layer.
     *
     * The signal that the load was native is that no `Piwik\` counterpart alias is
     * created: when composer's `Matomo\Plugins\` root finds the file directly the
     * alias autoloader never runs. Without that root the alias autoloader resolves
     * the class by swapping to `Piwik\` and creates the alias, so this assertion
     * fails - which makes this a red/green guard for the composer root itself.
     */
    public function testRenamedPluginClassAutoloadsNativelyUnderMatomoNamespace()
    {
        $matomoClass = \Matomo\Plugins\ExamplePlugin\AutoloadFixture\MatomoNamespacedClass::class;
        $piwikAlias = 'Piwik\Plugins\ExamplePlugin\AutoloadFixture\MatomoNamespacedClass';

        $this->assertFalse(class_exists($matomoClass, false), 'fixture class must not be preloaded');
        $this->assertFalse(class_exists($piwikAlias, false), 'no Piwik\ alias should exist before the load');

        $instance = new $matomoClass();

        $this->assertInstanceOf($matomoClass, $instance);
        $this->assertFalse(
            class_exists($piwikAlias, false),
            'Matomo\Plugins\ class must load natively and must not create a Piwik\ alias'
        );
    }

    /**
     * An un-renamed plugin class (still in `Piwik\Plugins\`) under the standard
     * plugins/ folder must keep autoloading natively - no regression from adding
     * the `Matomo\Plugins\` root.
     */
    public function testUnrenamedPluginClassStillAutoloadsUnderPiwikNamespace()
    {
        $piwikClass = \Piwik\Plugins\ExamplePlugin\ExamplePlugin::class;

        $this->assertTrue(class_exists($piwikClass), 'Piwik\Plugins\ class must still autoload');
    }

    /**
     * A plugin class installed in a custom configured plugin directory must
     * autoload under both the `Piwik\Plugins\` and `Matomo\Plugins\` prefixes, so
     * plugins living outside the standard plugins folder keep loading regardless of
     * their migration state.
     */
    public function testCustomPluginDirClassAutoloadsUnderBothPrefixes()
    {
        $piwikClass = \Piwik\Plugins\CustomDirPlugin\CustomClass::class;
        $matomoClass = \Matomo\Plugins\CustomDirPlugin\MatomoCustomClass::class;

        $this->assertFalse(class_exists($piwikClass, false), 'Piwik\ fixture must not be preloaded');
        $this->assertFalse(class_exists($matomoClass, false), 'Matomo\ fixture must not be preloaded');

        $piwikInstance = new $piwikClass();
        $matomoInstance = new $matomoClass();

        $this->assertInstanceOf($piwikClass, $piwikInstance);
        $this->assertInstanceOf($matomoClass, $matomoInstance);
    }
}
