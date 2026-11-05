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
use Matomo\Exception\DI\DependencyException;
use Matomo\Exception\DI\NotFoundException;
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
            $oppositeName = self::oppositeName($name);
            if ($oppositeName !== null) {
                try {
                    return parent::get($oppositeName);
                } catch (\Throwable $e2) {
                    // Opposite key also failed; throw the original below.
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
            $oppositeName = self::oppositeName($name);
            if ($oppositeName !== null) {
                try {
                    return parent::make($oppositeName, $parameters);
                } catch (\Throwable $e2) {
                    // Opposite key also failed; throw the original below.
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
     * Returns the opposite-namespace container key for a `Matomo\`
     * root-namespace entry name, or null when the name is not a root-namespace key.
     *
     * During the 7.x release line the root namespace is `Matomo\` but un-migrated
     * plugins still request core services under their deprecated `Piwik\` names
     * (e.g. the logger interface and auth implementations) and still register
     * their own services under them. {@see LegacyAutoloader} aliases the classes
     * via {@see class_alias()}, but PHP-DI container keys are strings: a key
     * misses its opposite-namespace definition and autowiring an interface fails
     * with "not instantiable". This helper rewrites the key so {@see get()} and
     * {@see make()} can retry under the opposite-namespace name, honouring the
     * same facade/exceptions-map rules as the alias layer. The alias layer is
     * removed in Matomo 8.0.
     */
    private static function oppositeName(string $name): ?string
    {
        if (!\LegacyAutoloader::isRootNamespaceName($name)) {
            return null;
        }

        $oppositeName = \LegacyAutoloader::getOppositeName($name);

        if ($oppositeName === $name) {
            return null;
        }

        return $oppositeName;
    }
}
