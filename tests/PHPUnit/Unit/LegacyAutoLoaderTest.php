<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit;

/**
 * @group Core
 * @group LegacyAutoLoader
 */
class LegacyAutoLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var callable|null
     */
    private $fixtureAutoloader;

    protected function setUp(): void
    {
        // Static state is not backed up (`backupStaticAttributes="false"`).
        \LegacyAutoloader::reset();

        // Dedicated fixtures live in tests/resources/ (not on any PSR-4 root),
        // so a tiny test-local autoloader maps Matomo\<Name> to that file. The
        // dedicated fixtures keep these tests order-independent: vendor classes
        // (e.g. Piwik\Ini\IniWriter) are touched by other tests and would mask
        // eager-aliasing regressions.
        $this->fixtureAutoloader = function ($class) {
            if (str_starts_with($class, 'Matomo\\')) {
                $file = PIWIK_INCLUDE_PATH . '/tests/resources/Matomo' . substr($class, 7) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        };
        spl_autoload_register($this->fixtureAutoloader);
    }

    protected function tearDown(): void
    {
        spl_autoload_unregister($this->fixtureAutoloader);
        $this->fixtureAutoloader = null;

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

        $class = new \Matomo\ClassNotFound();
    }

    public function testNotExistingPiwikClassStillFails()
    {
        $this->expectException(\Error::class);

        $class = new \Piwik\ClassNotFound();
    }

    public function testEagerAliasOnAutoload()
    {
        $class = new \Matomo\EagerAliasFixture();

        $this->assertTrue(class_exists('Matomo\EagerAliasFixture', false));
        // The Piwik\ alias must exist without any Piwik\ autoload being triggered.
        $this->assertTrue(class_exists('Piwik\EagerAliasFixture', false));
        $this->assertSame(\Matomo\EagerAliasFixture::class, get_class($class));
    }

    public function testAliasResolvesInReturnType()
    {
        $obj = new \Matomo\TypeCheckFixture();

        $fn = static function () use ($obj): \Piwik\TypeCheckFixture {
            return $obj;
        };

        // Throws TypeError when the Piwik\ name is not declared (no autoload in
        // return-type positions); passes only because the eager alias exists.
        $this->assertSame($obj, $fn());
    }

    public function testAliasResolvesInParameterType()
    {
        $obj = new \Matomo\TypeCheckFixture();

        $fn = static function (\Piwik\TypeCheckFixture $x): bool {
            return true;
        };

        $this->assertTrue($fn($obj));
    }

    public function testAliasResolvesInInstanceofVariableClass()
    {
        $obj = new \Matomo\TypeCheckFixture();

        $class = 'Piwik\TypeCheckFixture';

        $this->assertTrue($obj instanceof $class);
    }

    public function testAliasResolvesInIsA()
    {
        $obj = new \Matomo\TypeCheckFixture();

        $this->assertTrue(is_a($obj, 'Piwik\TypeCheckFixture'));
    }

    public function testCatchUpAliasesAlreadyDeclaredClasses()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/MatomoCatchUpFixture.php';

        // The class was declared before any autoload of it happened; the catch-up
        // pass (normally run inside LegacyAutoloader::register()) must alias it.
        \LegacyAutoloader::catchUpDeclaredClasses();

        $this->assertTrue(class_exists('Matomo\CatchUpFixture', false));
        $this->assertTrue(class_exists('Piwik\CatchUpFixture', false));
    }

    public function testFacadeAliasesBidirectionally()
    {
        $this->assertTrue(class_exists('Piwik\Piwik'));
        $this->assertTrue(class_exists('Matomo\Matomo', false));

        // Pure string operations; no autoloading involved.
        $this->assertSame('Matomo\Matomo', \LegacyAutoloader::getOppositeName('Piwik\Piwik'));
        $this->assertSame('Piwik\Piwik', \LegacyAutoloader::getOppositeName('Matomo\Matomo'));
    }

    public function testExceptionMapRedirectsShortNameChange()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/MatomoShortNameFixture.php';

        \LegacyAutoloader::setExceptionMap(['Piwik\ShortName' => 'Matomo\ShortNameTarget']);

        $obj = new \Piwik\ShortName();

        $this->assertInstanceOf(\Matomo\ShortNameTarget::class, $obj);
        // Reverse direction.
        $this->assertInstanceOf(\Piwik\ShortName::class, new \Matomo\ShortNameTarget());
    }

    public function testIsRootNamespaceName()
    {
        $this->assertTrue(\LegacyAutoloader::isRootNamespaceName('Piwik\Foo'));
        $this->assertTrue(\LegacyAutoloader::isRootNamespaceName('Matomo\Foo'));
        $this->assertTrue(\LegacyAutoloader::isRootNamespaceName('\Piwik\Foo'));
        $this->assertTrue(\LegacyAutoloader::isRootNamespaceName('\\Matomo\Foo'));
        $this->assertFalse(\LegacyAutoloader::isRootNamespaceName('Piwik'));
        $this->assertFalse(\LegacyAutoloader::isRootNamespaceName('Matomo'));
        $this->assertFalse(\LegacyAutoloader::isRootNamespaceName('PiwikFoo\Bar'));
        $this->assertFalse(\LegacyAutoloader::isRootNamespaceName('My\Piwik\Foo'));
        $this->assertFalse(\LegacyAutoloader::isRootNamespaceName(''));
    }

    public function testCanonicalizeFacadeBothDirections()
    {
        $this->assertSame('Matomo\Matomo', \LegacyAutoloader::canonicalize('Piwik\Piwik'));
        $this->assertSame('Matomo\Matomo', \LegacyAutoloader::canonicalize('Matomo\Matomo'));
        $this->assertSame('\Matomo\Matomo', \LegacyAutoloader::canonicalize('\Piwik\Piwik'));
    }

    public function testCanonicalizePreservesLeadingBackslashes()
    {
        $this->assertSame('\Matomo\Plugins\X', \LegacyAutoloader::canonicalize('\Piwik\Plugins\X'));
        $this->assertSame('\\Matomo\Plugins\X', \LegacyAutoloader::canonicalize('\\Piwik\Plugins\X'));
        $this->assertSame('\Matomo\Plugins\X', \LegacyAutoloader::canonicalize('\Matomo\Plugins\X'));
    }

    public function testCanonicalizeHonoursExceptionMap()
    {
        \LegacyAutoloader::setExceptionMap(['Piwik\ShortName' => 'Matomo\ShortNameTarget']);

        $this->assertSame('Matomo\ShortNameTarget', \LegacyAutoloader::canonicalize('Piwik\ShortName'));
        // The reverse map is not consulted: Matomo names are already canonical.
        $this->assertSame('Matomo\ShortNameTarget', \LegacyAutoloader::canonicalize('Matomo\ShortNameTarget'));
        $this->assertSame('Matomo\Other', \LegacyAutoloader::canonicalize('Piwik\Other'));
    }

    public function testCanonicalizeLeavesNonRootAndCanonicalNamesUnchanged()
    {
        $this->assertSame('Matomo\Foo', \LegacyAutoloader::canonicalize('Matomo\Foo'));
        $this->assertSame('Piwik', \LegacyAutoloader::canonicalize('Piwik'));
        $this->assertSame('My\Piwik\Foo', \LegacyAutoloader::canonicalize('My\Piwik\Foo'));
        $this->assertSame('', \LegacyAutoloader::canonicalize(''));
    }

    public function testCanonicalizeSwapsRootPrefix()
    {
        $this->assertSame('Matomo\Plugins\Foo\Bar', \LegacyAutoloader::canonicalize('Piwik\Plugins\Foo\Bar'));
        // Only the facade's both segments swap; interior facade segments stay.
        $this->assertSame('Matomo\Piwik\Foo', \LegacyAutoloader::canonicalize('Piwik\Piwik\Foo'));
    }
}
