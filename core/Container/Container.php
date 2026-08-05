<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Container;

use DI\Container as DIContainer;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Proxy\ProxyFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Proxy class for our DI Container
 * @see DIContainer, ContainerInterface
 */
class Container extends DIContainer implements ContainerInterface
{
    public function __construct(
        ?MutableDefinitionSource $definitionSource = null,
        ?ProxyFactoryInterface $proxyFactory = null,
        ?ContainerInterface $wrapperContainer = null
    ) {
        parent::__construct($definitionSource, $proxyFactory, $wrapperContainer);
        // ensure this container class can be resolved
        $this->resolvedEntries[self::class] = $this;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $name Container entry name.
     * @return ($name is class-string<T> ? T : mixed)
     */
    public function get(string $name): mixed
    {
        try {
            return parent::get($name);
        } catch (\Throwable $e) {
            $matomoName = self::legacyNameToMatomo($name);
            if ($matomoName !== null) {
                try {
                    return parent::get($matomoName);
                } catch (\Throwable $e2) {
                    // Matomo\ key also failed; throw the original below.
                }
            }
            if ($e instanceof \DI\NotFoundException) {
                throw new \Matomo\Exception\DI\NotFoundException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * @template T of object
     * @param class-string<T>|string $name Container entry name.
     * @return ($name is class-string<T> ? T : mixed)
     */
    public function make(string $name, array $parameters = []): mixed
    {
        try {
            return parent::make($name, $parameters);
        } catch (\Throwable $e) {
            $matomoName = self::legacyNameToMatomo($name);
            if ($matomoName !== null) {
                try {
                    return parent::make($matomoName, $parameters);
                } catch (\Throwable $e2) {
                    // Matomo\ key also failed; throw the original below.
                }
            }
            if ($e instanceof \DI\NotFoundException) {
                throw new \Matomo\Exception\DI\NotFoundException($e->getMessage(), $e->getCode(), $e);
            }
            if ($e instanceof \DI\DependencyException) {
                throw new \Matomo\Exception\DI\DependencyException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    /**
     * Returns the `Matomo\` counterpart of a deprecated `Piwik\` container
     * entry name, or null when the name is not a `Piwik\` root-namespace key.
     *
     * During the 6.x release line the root namespace is `Matomo\` but un-migrated
     * bundled-submodule plugins still request core services under their deprecated
     * `Piwik\` names (e.g. `Piwik\Log\LoggerInterface`). {@see LegacyAutoloader}
     * aliases the classes via {@see class_alias()}, but PHP-DI container keys are
     * strings: a `Piwik\` key misses its `Matomo\` definition and resolution
     * fails — either with a `NotFoundException`/`InvalidDefinition` (\Exception)
     * when autowiring an interface, or with a `TypeError` (\Error, e.g. when
     * autowiring the container itself whose parent constructor rejects null).
     * This helper rewrites the key so {@see get()} and {@see make()} can retry
     * under the canonical `Matomo\` name; the catch is `\Throwable` so the retry
     * runs for both the Exception and Error failure paths. The alias layer is
     * removed in 7.0.
     */
    private static function legacyNameToMatomo(string $name): ?string
    {
        if (strncmp($name, 'Piwik\\', 6) !== 0) {
            return null;
        }
        return 'Matomo\\' . substr($name, 6);
    }

    public function injectOn(object $instance): object
    {
        try {
            return parent::injectOn($instance);
        } catch (\DI\DependencyException $e) {
            throw new \Matomo\Exception\DI\DependencyException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
