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
    protected function setUp(): void
    {
        // Keep the deprecation recorder inert during tests: no real PSR-3 sink side
        // effects, and a clean buffer so each test observes only its own loads.
        \LegacyAutoloader::setDeprecationSink(null);
        \LegacyAutoloader::setPiwikToMatomoExceptions([]);
        \LegacyAutoloader::setPluginRootsResolver(null);
        \LegacyAutoloader::clearRecordedDeprecations();
    }

    protected function tearDown(): void
    {
        \LegacyAutoloader::setDeprecationSink(null);
        \LegacyAutoloader::setPiwikToMatomoExceptions([]);
        \LegacyAutoloader::setPluginRootsResolver(null);
        \LegacyAutoloader::clearRecordedDeprecations();
        unset($GLOBALS['PIWIK_TRACKER_MODE']);
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

    public function testPiwikReferenceToMigratedClassRecordsDeprecation()
    {
        new \Piwik\Ini\IniWritingException();

        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(1, $events);
        $this->assertSame('Piwik\Ini\IniWritingException', $events[0]['piwik']);
        $this->assertSame('Matomo\Ini\IniWritingException', $events[0]['matomo']);
    }

    public function testDeprecationFiresAtMostOncePerDistinctClass()
    {
        // The same deprecated class referenced twice: the alias is cached after the first
        // resolution, so the autoloader (and thus the recorder) runs only once.
        class_exists('Piwik\Ini\IniReadingException');
        class_exists('Piwik\Ini\IniReadingException');

        // A distinct deprecated class records a second, separate event.
        class_exists('Piwik\Ini\IniReader');

        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(2, $events);
        $this->assertSame('Piwik\Ini\IniReadingException', $events[0]['piwik']);
        $this->assertSame('Piwik\Ini\IniReader', $events[1]['piwik']);
    }

    public function testNoDeprecationRecordedInTrackerRequest()
    {
        $GLOBALS['PIWIK_TRACKER_MODE'] = true;

        // The alias still resolves for correctness, but no deprecation is recorded: the
        // tracker hot path incurs no recording cost.
        $resolved = class_exists('Piwik\Network\IPv4');
        $this->assertTrue($resolved);

        $this->assertSame([], \LegacyAutoloader::getRecordedDeprecations());
    }

    public function testExceptionsMapRedirectsToDifferentShortName()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/LegacyAutoloader/ExceptionsMapTarget.php';
        \LegacyAutoloader::setPiwikToMatomoExceptions(array(
            'Piwik\Test\RenamedByMap' => 'Matomo\Test\ExceptionsMapTarget',
        ));

        $instance = new \Piwik\Test\RenamedByMap();

        $this->assertInstanceOf(\Matomo\Test\ExceptionsMapTarget::class, $instance);
        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(1, $events);
        $this->assertSame('Piwik\Test\RenamedByMap', $events[0]['piwik']);
        $this->assertSame('Matomo\Test\ExceptionsMapTarget', $events[0]['matomo']);
    }

    public function testPluginFromBacktraceAttributesToFirstPluginFrame()
    {
        $roots = array('/var/www/plugins/', '/opt/extra-plugins/');
        $trace = array(
            array('file' => '/var/www/LegacyAutoloader.php', 'line' => 1, 'function' => 'load_class'),
            array('file' => '/var/www/plugins/CoreAdminHome/Controller.php', 'line' => 42),
            array('file' => '/var/www/plugins/SomeOther/Foo.php', 'line' => 7),
        );

        // First-seen wins: the deprecation is attributed to the plugin whose code first
        // referenced the deprecated class, not to callers further up the stack.
        $this->assertSame('CoreAdminHome', \LegacyAutoloader::pluginFromBacktrace($trace, $roots));
    }

    public function testPluginFromBacktraceHandlesCustomPluginDir()
    {
        $roots = array('/var/www/plugins/', '/opt/extra-plugins/');
        $trace = array(
            array('file' => '/opt/extra-plugins/CustomDirPlugin/API.php', 'line' => 10),
        );

        $this->assertSame('CustomDirPlugin', \LegacyAutoloader::pluginFromBacktrace($trace, $roots));
    }

    public function testPluginFromBacktraceToleratesRootWithoutTrailingSlash()
    {
        $roots = array('/var/www/plugins');
        $trace = array(
            array('file' => '/var/www/plugins/TagManager/Model.php', 'line' => 3),
        );

        $this->assertSame('TagManager', \LegacyAutoloader::pluginFromBacktrace($trace, $roots));
    }

    public function testPluginFromBacktraceReturnsNullForCoreOrigin()
    {
        $roots = array('/var/www/plugins/');
        $trace = array(
            array('file' => '/var/www/LegacyAutoloader.php', 'line' => 1),
            array('file' => '/var/www/core/Controller.php', 'line' => 5),
        );

        // A deprecation triggered from core (not under any plugin root) is unattributed.
        $this->assertNull(\LegacyAutoloader::pluginFromBacktrace($trace, $roots));
    }

    public function testPluginFromBacktraceReturnsNullWhenNoFileFrames()
    {
        $roots = array('/var/www/plugins/');
        $trace = array(array('function' => 'eval'));

        $this->assertNull(\LegacyAutoloader::pluginFromBacktrace($trace, $roots));
    }

    public function testRecordedDeprecationIsAttributedToCallingPlugin()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/custompluginsdir/CustomDirPlugin/LegacyAliasCaller.php';

        // Wire the resolver to the real production source of plugin roots. The test
        // bootstrap registers tests/resources/custompluginsdir as a plugin directory, so
        // the fixture above is attributable to CustomDirPlugin.
        \LegacyAutoloader::setPluginRootsResolver(array('Piwik\Plugin\Manager', 'getPluginsDirectories'));

        \Matomo\Test\LegacyAliasCaller::trigger();

        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(1, $events);
        $this->assertSame('Piwik\Network\IPv6', $events[0]['piwik']);
        $this->assertSame('Matomo\Network\IPv6', $events[0]['matomo']);
        $this->assertSame('CustomDirPlugin', $events[0]['plugin']);
    }

    public function testRecordedDeprecationPluginIsNullWithoutResolver()
    {
        // With no resolver wired, attribution is skipped: the event still records, but
        // `plugin` stays null (to be grouped as core by the diagnostic).
        class_exists('Piwik\Network\IP');

        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(1, $events);
        $this->assertNull($events[0]['plugin']);
    }

    public function testExplicitDeprecationSinkReceivesFullEvent()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/LegacyAutoloader/SinkProbe.php';
        \LegacyAutoloader::setPluginRootsResolver(function () {
            return array();
        });

        $received = null;
        \LegacyAutoloader::setDeprecationSink(function ($event) use (&$received) {
            $received = $event;
        });

        class_exists('Piwik\Test\SinkProbe');

        $this->assertNotNull($received);
        $this->assertSame('Piwik\Test\SinkProbe', $received['piwik']);
        $this->assertSame('Matomo\Test\SinkProbe', $received['matomo']);
        // With an empty roots list the call is unattributable, so plugin is null.
        $this->assertNull($received['plugin']);
    }

    public function testDefaultSinkRoutesDeprecationToPsr3Logger()
    {
        require_once PIWIK_INCLUDE_PATH . '/tests/resources/LegacyAutoloader/DefaultSinkProbe.php';

        // No explicit sink: the default sink must fetch the PSR-3 logger from the
        // container and log at `notice` level with the deprecation event as context.
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with(
                $this->isType('string'),
                array(
                    'piwik'  => 'Piwik\Test\DefaultSinkProbe',
                    'matomo' => 'Matomo\Test\DefaultSinkProbe',
                    'plugin' => null,
                )
            );

        $container = new \Piwik\Container\Container(new \DI\Definition\Source\DefinitionArray());
        $container->set('Piwik\Log\LoggerInterface', $logger);
        \Piwik\Container\StaticContainer::push($container);

        try {
            class_exists('Piwik\Test\DefaultSinkProbe');
        } finally {
            \Piwik\Container\StaticContainer::pop();
        }

        $events = \LegacyAutoloader::getRecordedDeprecations();
        $this->assertCount(1, $events);
    }
}
