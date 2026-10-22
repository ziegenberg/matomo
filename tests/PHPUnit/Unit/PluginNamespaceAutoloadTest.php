<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

/**
 * Covers the dual `Piwik\Plugins\` / `Matomo\Plugins\` autoload roots kept
 * through 7.0, the dual-prefix plugin-directory autoloader
 * ({@see \Piwik\Plugin\Manager::registerPluginDirAutoload()}), and the
 * `include_once` patch on composer's include closure that prevents class
 * redeclaration when one plugin file is reachable under both prefixes.
 *
 * @group Core
 * @group PluginNamespaceAutoload
 */
class PluginNamespaceAutoloadTest extends \PHPUnit\Framework\TestCase
{
    public function testComposerDualRootLoadsUnderMatomoPrefix()
    {
        // The file declares `Piwik\Plugins\ExamplePlugin\ExamplePlugin`; the new
        // Matomo\Plugins\ root resolves the same file and the alias layer bridges
        // the names, so both names end up being the same class.
        $obj = new \Matomo\Plugins\ExamplePlugin\ExamplePlugin();

        $this->assertTrue(class_exists('Piwik\Plugins\ExamplePlugin\ExamplePlugin', false));
        $this->assertTrue(class_exists('Matomo\Plugins\ExamplePlugin\ExamplePlugin', false));
        $this->assertSame('Piwik\Plugins\ExamplePlugin\ExamplePlugin', get_class($obj));
    }

    public function testUnrenamedPluginClassStillLoadsNatively()
    {
        // CustomDirPlugin lives in a custom plugin directory; its class declares
        // Piwik\Plugins\ and must still load natively via the plugin-dir
        // autoloader, eagerly creating the Matomo\ alias.
        $obj = new \Piwik\Plugins\CustomDirPlugin\CustomClass();

        $this->assertSame('Piwik\Plugins\CustomDirPlugin\CustomClass', get_class($obj));
        $this->assertTrue(class_exists('Matomo\Plugins\CustomDirPlugin\CustomClass', false));
    }

    public function testCustomPluginDirClassLoadsUnderBothPrefixes()
    {
        // Un-renamed class in a custom plugin directory.
        $piwikCustom = new \Piwik\Plugins\CustomDirPlugin\CustomClass();
        $matomoCustom = new \Matomo\Plugins\CustomDirPlugin\CustomClass();
        $this->assertSame(get_class($piwikCustom), get_class($matomoCustom));
        $this->assertTrue($matomoCustom instanceof \Piwik\Plugins\CustomDirPlugin\CustomClass);

        // Renamed class (Matomo\Plugins\) in a custom plugin directory: the
        // plugin-dir autoloader must accept the Matomo\ prefix too, and the
        // Piwik\ alias must be created without any class redeclaration.
        $matomoDir = new \Matomo\Plugins\MatomoDirPlugin\MatomoDirClass();
        $piwikDir = new \Piwik\Plugins\MatomoDirPlugin\MatomoDirClass();
        $this->assertSame(get_class($matomoDir), get_class($piwikDir));
        $this->assertSame('Matomo\Plugins\MatomoDirPlugin\MatomoDirClass', get_class($piwikDir));
        $this->assertTrue($piwikDir instanceof \Matomo\Plugins\MatomoDirPlugin\MatomoDirClass);
    }

    public function testComposerIncludeIsIdempotent()
    {
        $loader = $this->getComposerClassLoader();
        $this->assertNotNull($loader);

        // Bypass the alias layer: resolve the same plugin file under both PSR-4
        // roots directly through composer's ClassLoader. Composer includes with
        // `include`, so without the LegacyAutoloader include_once patch the second
        // resolution would re-include the file and fatal with "Cannot declare
        // class". The patch makes the second include a no-op.
        $loader->loadClass('Piwik\Plugins\ExamplePlugin\ExamplePlugin');
        $loader->loadClass('Matomo\Plugins\ExamplePlugin\ExamplePlugin');

        $this->assertTrue(class_exists('Piwik\Plugins\ExamplePlugin\ExamplePlugin', false));
    }

    /**
     * @return \Composer\Autoload\ClassLoader|null
     */
    private function getComposerClassLoader()
    {
        foreach (spl_autoload_functions() as $autoloadFunction) {
            if (
                is_array($autoloadFunction)
                && isset($autoloadFunction[0])
                && is_object($autoloadFunction[0])
                && get_class($autoloadFunction[0]) === 'Composer\Autoload\ClassLoader'
            ) {
                return $autoloadFunction[0];
            }
        }

        return null;
    }
}
