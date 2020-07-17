<?php

declare(strict_types=1);

/*
 * This file is part of Flight Routing.
 *
 * PHP version 7.1 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flight\Routing;

use Flight\Routing\Interfaces\CallableResolverInterface;
use Flight\Routing\Interfaces\RouteCollectorInterface;
use Flight\Routing\Interfaces\RouteGroupInterface;
use Flight\Routing\Interfaces\RouteInterface;
use Flight\Routing\Interfaces\RouterInterface;
use Flight\Routing\Interfaces\RouterProxyInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class RouterProxy implements RouterProxyInterface
{
    /**
     * @var RouteCollectorInterface
     */
    protected $routeCollector;

    /**
     * @param ResponseFactoryInterface  $responseFactory
     * @param RouterInterface           $router
     * @param RouteCollectorInterface   $routeCollector
     * @param CallableResolverInterface $callableResolver
     * @param null|ContainerInterface   $container
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        RouterInterface $router,
        RouteCollectorInterface $routeCollector = null,
        CallableResolverInterface $callableResolver = null,
        ContainerInterface $container = null
    ) {
        $this->routeCollector = $routeCollector
            ?? new RouteCollector($responseFactory, $router, $callableResolver, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollector(): RouteCollectorInterface
    {
        return $this->routeCollector;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $pattern, $callable): RouteInterface
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function put(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function patch(string $pattern, $callable): RouteInterface
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $pattern, $callable): RouteInterface
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function options(string $pattern, $callable): RouteInterface
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function any(string $pattern, $callable): RouteInterface
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $methods, string $pattern, $callable): RouteInterface
    {
        return $this->routeCollector->map($methods, $pattern, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function group(array $attributes, $callable): RouteGroupInterface
    {
        return $this->routeCollector->group($attributes, $callable);
    }
}
