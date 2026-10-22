<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Container;

use DI\Container as DIContainer;
use DI\Definition\Source\MutableDefinitionSource;
use DI\Proxy\ProxyFactoryInterface;
use Piwik\Exception\DI\DependencyException;
use Piwik\Exception\DI\NotFoundException;
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
                throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
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
                throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
            }
            if ($e instanceof \DI\DependencyException) {
                throw new DependencyException($e->getMessage(), $e->getCode(), $e);
            }
            throw $e;
        }
    }

    public function injectOn(object $instance): object
    {
        try {
            return parent::injectOn($instance);
        } catch (\DI\DependencyException $e) {
            throw new DependencyException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the `Matomo\` counterpart of a deprecated `Piwik\` container
     * entry name, or null when the name is not a `Piwik\` root-namespace key.
     *
     * During the 7.x release line the root namespace is `Matomo\` but un-migrated
     * plugins still request core services under their deprecated `Piwik\` names
     * (e.g. `Piwik\Log\LoggerInterface`). {@see LegacyAutoloader} aliases the
     * classes via {@see class_alias()}, but PHP-DI container keys are strings: a
     * `Piwik\` key misses its `Matomo\` definition and autowiring an interface
     * fails with "not instantiable". This helper rewrites the key so
     * {@see get()} and {@see make()} can retry under the canonical `Matomo\`
     * name, honouring the same facade/exceptions-map rules as the alias layer
     * (e.g. `Piwik\Piwik` -> `Matomo\Matomo`). The alias layer is removed in
     * Matomo 8.0.
     */
    private static function legacyNameToMatomo(string $name): ?string
    {
        if (strncmp($name, 'Piwik\\', 6) !== 0) {
            return null;
        }

        $matomoName = \LegacyAutoloader::getOppositeName($name);

        if (strncmp($matomoName, 'Matomo\\', 7) !== 0) {
            return null;
        }

        return $matomoName;
    }
}
