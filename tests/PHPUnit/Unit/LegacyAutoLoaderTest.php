<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

/**
 * Verifies the eager bidirectional alias layer ({@see \LegacyAutoloader}) that
 * keeps un-migrated `Piwik\` code working through the `Piwik\` -> `Matomo\`
 * root-namespace migration (Matomo 6.0).
 *
 * The alias layer eagerly `class_alias`es every loaded `Matomo\` class to its
 * `Piwik\` counterpart (and vice versa) so a class referenced under either
 * namespace resolves to the same class entry — including in PHP type-check
 * positions (return/param types, `instanceof`, `is_a()`) that do NOT trigger
 * autoloading, which a lazy alias layer could not satisfy.
 *
 * @group Core
 * @group LegacyAutoLoader
 */
class LegacyAutoLoaderTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // The test-configurable exceptions map is static state not backed up by
        // phpunit (backupStaticAttributes="false"). Clear it before every test.
        \LegacyAutoloader::reset();
    }

    public function testPackageClassWorks()
    {
        $class = new \Piwik\Ini\IniWriter();

        $this->assertInstanceOf(\Matomo\Ini\IniWriter::class, $class);
    }

    public function testPackageClassStaticMethodWorks()
    {
        $ip = '123.13.12.123';

        $binary = \Piwik\Network\IPUtils::stringToBinaryIP($ip);

        $this->assertEquals($ip, \Matomo\Network\IPUtils::binaryToStringIP($binary));
    }

    public function testManuallyRequiredClassWorks()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/MatomoDummyClass.php';

        $class = new \Piwik\DummyClass();

        $this->assertInstanceOf(\Matomo\DummyClass::class, $class);
    }

    public function testNotExistingMatomoClassStillFails()
    {
        $this->expectException(\Error::class);

        new \Matomo\ClassNotFound();
    }

    public function testNotExistingPiwikClassStillFails()
    {
        $this->expectException(\Error::class);

        new \Piwik\ClassNotFound();
    }

    /**
     * Loading a `Matomo\` class eagerly creates the `Piwik\` alias, so a later
     * `Piwik\` reference resolves without triggering autoload and points at the
     * same class entry.
     */
    public function testEagerAliasCreatedWhenMatomoClassLoads()
    {
        // Force-load a Matomo\ class through the autoloader. class_exists(..., true)
        // triggers the autoload chain, which runs LegacyAutoloader::load() and
        // eagerly class_aliases the Piwik\ counterpart at load time. No Piwik\
        // reference is made in this test, so any Piwik\ alias must have been
        // created eagerly, not lazily on first Piwik\ use.
        $this->assertTrue(class_exists('Matomo\\SettingsServer', true), 'Matomo\\SettingsServer is autoloadable');

        $this->assertTrue(class_exists('Piwik\\SettingsServer', false), 'Piwik\\ alias created eagerly on Matomo\\ load');
        $this->assertTrue(new \Piwik\SettingsServer() instanceof \Matomo\SettingsServer, 'Piwik\\ resolves to the same class entry');
    }

    /**
     * A `Piwik\X` return type is satisfied by a returned `Matomo\X` instance,
     * because loading `Matomo\X` eagerly aliased `Piwik\X` to it. This is the
     * type-check position a lazy alias layer could not satisfy.
     */
    public function testReturnTypeCheckAgainstPiwikNamespacePasses()
    {
        $value = \Matomo\Tests\Unit\EagerAliasFixture::returnMatomoAsPiwik();

        $this->assertInstanceOf(\Piwik\SettingsServer::class, $value);
        $this->assertInstanceOf(\Matomo\SettingsServer::class, $value);
    }

    public function testParamTypeCheckAgainstPiwikNamespacePasses()
    {
        $value = \Matomo\Tests\Unit\EagerAliasFixture::acceptPiwikParam(new \Matomo\SettingsServer());

        $this->assertInstanceOf(\Matomo\SettingsServer::class, $value);
    }

    public function testInstanceofVariableAndIsaAgainstPiwikNamespacePass()
    {
        $obj = new \Matomo\SettingsServer();
        $piwikClass = 'Piwik\\SettingsServer';

        $this->assertTrue($obj instanceof $piwikClass, 'instanceof with a variable Piwik\\ name resolves');
        $this->assertTrue(is_a($obj, 'Piwik\\SettingsServer'), 'is_a() against a Piwik\\ name resolves');
    }

    /**
     * The one class whose short name changes — the facade `Piwik\Piwik` <->
     * `Matomo\Matomo` — aliases correctly in both directions, including in type
     * positions.
     */
    public function testFacadeAliasResolvesBidirectionally()
    {
        $matomo = new \Matomo\Matomo();

        $this->assertTrue(class_exists('Piwik\\Piwik', false), 'Piwik\\Piwik alias created eagerly');
        $this->assertTrue($matomo instanceof \Piwik\Piwik, 'Matomo\\Matomo instanceof Piwik\\Piwik');
        $this->assertTrue(new \Piwik\Piwik() instanceof \Matomo\Matomo, 'Piwik\\Piwik instanceof Matomo\\Matomo');
    }

    /**
     * A class configured in the exceptions map (short name changes beyond the
     * facade) is redirected to its configured target instead of prefix-swapped.
     */
    public function testExceptionsMapRedirectsConfiguredClass()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/MatomoRenamedFacade.php';

        \LegacyAutoloader::setExceptionMap([
            'Piwik\\OldFacade' => 'Matomo\\RenamedFacade',
        ]);

        // Prefix-swapping would have looked for Matomo\OldFacade (which does not
        // exist) and the alias would have failed. The exceptions map redirected
        // the lookup to Matomo\RenamedFacade instead.
        $class = new \Piwik\OldFacade();

        $this->assertInstanceOf(\Matomo\RenamedFacade::class, $class);
    }
}

/**
 * Stand-alone functions with `Piwik\` type positions, used to assert that eager
 * aliasing satisfies PHP type checks that do not trigger autoloading. Top-level
 * (not closures) so the `Piwik\` types are resolved at call time, by which point
 * the eager alias already exists.
 */
class EagerAliasFixture
{
    public static function returnMatomoAsPiwik(): \Piwik\SettingsServer
    {
        return new \Matomo\SettingsServer();
    }

    public static function acceptPiwikParam(\Piwik\SettingsServer $value): \Matomo\SettingsServer
    {
        return $value;
    }
}
