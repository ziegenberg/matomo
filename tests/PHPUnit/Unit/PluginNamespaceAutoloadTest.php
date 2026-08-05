<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3.0 or later
 */

namespace Matomo\Tests\Unit;

/**
 * Verifies the dual plugin-namespace autoload roots and the eager alias layer
 * introduced for the `Piwik\` -> `Matomo\` root-namespace migration.
 *
 * Through the 6.x release line both `Piwik\Plugins\` and `Matomo\Plugins\` map to
 * the plugin directories so plugins keep loading natively while they are renamed
 * batch by batch. The eager alias layer additionally `class_alias`es each loaded
 * `Matomo\Plugins\` class to its `Piwik\Plugins\` counterpart (and vice versa) at
 * load time, so type checks against either namespace name resolve to the same
 * class entry.
 *
 * @group Core
 * @group PluginNamespaceAutoload
 */
class PluginNamespaceAutoloadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * A renamed plugin class (declared in `Matomo\Plugins\`) under the standard
     * plugins/ folder must autoload natively via the `Matomo\Plugins\` PSR-4 root.
     *
     * The eager alias layer then creates the `Piwik\` counterpart alias at load
     * time, so both names resolve to the same class entry. The "alias absent
     * before the load" assertion proves the alias is created by this load
     * (eagerly), not pre-existing; the "alias present after" assertion proves the
     * eager alias fired.
     */
    public function testRenamedPluginClassAutoloadsNativelyUnderMatomoNamespace()
    {
        $matomoClass = \Matomo\Plugins\ExamplePlugin\AutoloadFixture\MatomoNamespacedClass::class;
        $piwikAlias = 'Piwik\Plugins\ExamplePlugin\AutoloadFixture\MatomoNamespacedClass';

        $this->assertFalse(class_exists($matomoClass, false), 'fixture class must not be preloaded');
        $this->assertFalse(class_exists($piwikAlias, false), 'no Piwik\ alias should exist before the load');

        $instance = new $matomoClass();

        $this->assertInstanceOf($matomoClass, $instance);
        $this->assertTrue(
            class_exists($piwikAlias, false),
            'Matomo\Plugins\ load must eagerly create the Piwik\ alias'
        );
        $this->assertTrue(
            new $piwikAlias() instanceof $matomoClass,
            'Piwik\ alias must resolve to the same class entry as the Matomo\ class'
        );
    }

    /**
     * An un-renamed plugin class (still in `Piwik\Plugins\`) under the standard
     * plugins/ folder must keep autoloading natively - no regression from adding
     * the `Matomo\Plugins\` root - and the eager alias layer must create the
     * `Matomo\` counterpart alias at load time.
     */
    public function testUnrenamedPluginClassStillAutoloadsUnderPiwikNamespace()
    {
        $piwikClass = \Piwik\Plugins\ExamplePlugin\AutoloadFixture\PiwikNamespacedClass::class;
        $matomoAlias = 'Matomo\Plugins\ExamplePlugin\AutoloadFixture\PiwikNamespacedClass';

        $this->assertFalse(class_exists($piwikClass, false), 'fixture class must not be preloaded');
        $this->assertFalse(class_exists($matomoAlias, false), 'no Matomo\ alias should exist before the load');

        $instance = new $piwikClass();

        $this->assertInstanceOf($piwikClass, $instance);
        $this->assertTrue(
            class_exists($matomoAlias, false),
            'Piwik\Plugins\ load must eagerly create the Matomo\ alias'
        );
        $this->assertTrue(
            new $matomoAlias() instanceof $piwikClass,
            'Matomo\ alias must resolve to the same class entry as the Piwik\ class'
        );
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
