<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Unit\Container;

use Piwik\Container\Container;
use Piwik\Exception\DI\NotFoundException;

/**
 * Covers the `\Throwable` DI-key retry in {@see Container::get()} and
 * {@see Container::make()}: a `Piwik\`-prefixed container key that misses its
 * `Matomo\` definition is retried under the `Matomo\` key.
 *
 * @group Core
 * @group Container
 */
class ContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return Container
     */
    private function createContainer()
    {
        // A fresh container needs an explicit definition source: passing null
        // (the default) reaches DI\Container::__construct() with `null` and
        // throws a TypeError (the very \Error class of container entries the
        // \Throwable retry was introduced for).
        return new Container(new \DI\Definition\Source\DefinitionArray([]));
    }

    public function testGetRetriesUnderMatomoKey()
    {
        $container = $this->createContainer();
        $stub = new class {
        };
        $container->set('Matomo\Test\RetryInterface', $stub);

        $this->assertSame($stub, $container->get('Piwik\Test\RetryInterface'));
    }

    public function testMakeRetriesUnderMatomoKey()
    {
        $container = $this->createContainer();
        $stub = new class {
        };
        $container->set('Matomo\Test\RetryInterface', $stub);

        $this->assertSame($stub, $container->make('Piwik\Test\RetryInterface'));
    }

    public function testFacadeKeyRetriesUnderMatomoKey()
    {
        $container = $this->createContainer();
        $stub = new class {
        };
        $container->set('Matomo\Matomo', $stub);

        // The facade short name changes (Piwik\Piwik -> Matomo\Matomo); the
        // retry must honour it instead of doing a naive prefix swap.
        $this->assertSame($stub, $container->get('Piwik\Piwik'));
    }

    public function testMakeFacadeKeyRetriesUnderMatomoKey()
    {
        $container = $this->createContainer();
        $stub = new class {
        };
        $container->set('Matomo\Matomo', $stub);

        $this->assertSame($stub, $container->make('Piwik\Piwik'));
    }

    public function testExceptionMapEntryRetriesUnderMatomoKey()
    {
        \LegacyAutoloader::setExceptionMap(['Piwik\Test\ShortName' => 'Matomo\Test\LongName']);

        try {
            $container = $this->createContainer();
            $stub = new class {
            };
            $container->set('Matomo\Test\LongName', $stub);

            $this->assertSame($stub, $container->get('Piwik\Test\ShortName'));
        } finally {
            \LegacyAutoloader::reset();
        }
    }

    public function testRetryCatchesThrowable()
    {
        $container = $this->createContainer();
        $container->set('Piwik\Test\Key', function () {
            throw new \Error('boom');
        });
        $stub = new class {
        };
        $container->set('Matomo\Test\Key', $stub);

        // The retry must catch \Throwable: the factory fails with an \Error, not
        // an \Exception, and the working Matomo\ entry must still be resolved.
        $this->assertSame($stub, $container->get('Piwik\Test\Key'));
    }

    public function testMissingPiwikKeyStillThrows()
    {
        $container = $this->createContainer();

        $this->expectException(NotFoundException::class);

        $container->get('Piwik\No\Such');
    }

    public function testNonPiwikKeyNotRewritten()
    {
        $container = $this->createContainer();
        $stub = new class {
        };
        $container->set('SomeKey', $stub);

        $this->assertSame($stub, $container->get('SomeKey'));

        $this->expectException(NotFoundException::class);

        // A non-Piwik\ missing key is not retried; it still throws the
        // converted NotFoundException.
        $container->get('AnotherMissingKey');
    }
}
