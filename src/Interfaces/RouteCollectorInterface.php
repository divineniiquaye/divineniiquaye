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

namespace Flight\Routing\Interfaces;

use Closure;
use Flight\Routing\Exceptions\RouteNotFoundException;
use Flight\Routing\Exceptions\UrlGenerationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

interface RouteCollectorInterface extends RequestHandlerInterface
{
    public const TYPE_REQUIREMENT = 1;

    public const TYPE_DEFAULT = 0;

    /**
     * Get route objects.
     *
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * Generate a URI from the named route.
     *
     * Takes the named route and any parameters, and attempts to generate a
     * URI from it. Additional router-dependent query may be passed.
     *
     * Once there are no missing parameters in the URI we will encode
     * the URI and prepare it for returning to the user. If the URI is supposed to
     * be absolute, we will return it as-is. Otherwise we will remove the URL's root.
     *
     * @param string         $routeName   route name
     * @param array|string[] $parameters  key => value option pairs to pass to the
     *                                    router for purposes of generating a URI; takes precedence over options
     *                                    present in route used to generate URI
     * @param array          $queryParams Optional query string parameters
     *
     * @throws UrlGenerationException if the route name is not known
     *                                or a parameter value does not match its regex
     *
     * @return string of fully qualified URL for named route
     */
    public function generateUri(string $routeName, array $parameters = [], array $queryParams = []): ?string;

    /**
     * Set the root controller namespace.
     *
     * @param string $rootNamespace
     *
     * @return RouteCollectorInterface
     */
    public function setNamespace(string $rootNamespace): self;

    /**
     * Get named route object.
     *
     * @param string $name Route name
     *
     * @throws RuntimeException If named route does not exist
     *
     * @return RouteInterface
     */
    public function getNamedRoute(string $name): RouteInterface;

    /**
     * Get the current route.
     *
     * @return null|RouteInterface
     */
    public function currentRoute(): ?RouteInterface;

    /**
     * Add this to keep the HTTP method when redirecting.
     *
     * redirection is temporary by default (code 302)
     *
     * @param bool $status
     *
     * @return RouteCollectorInterface
     */
    public function keepRequestMethod(bool $status = false): self;

    /**
     * Ge the current router used.
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface;

    /**
     * Set the global the middlewares stack attached to all routes.
     *
     * @param callable|MiddlewareInterface|string|string[] $middleware
     *
     * @return RouteCollectorInterface
     */
    public function addMiddlewares($middleware = []): self;

    /**
     * Set the route middleware and call it as a method on route.
     *
     * @param array<string,mixed> $middlewares [name => $middlewares]
     *
     * @return RouteCollectorInterface
     */
    public function routeMiddlewares($middlewares = []): self;

    /**
     * Get all middlewares from stack.
     *
     * @return MiddlewareInterface[]|string[]
     */
    public function getMiddlewaresStack(): array;

    /**
     * Adds parameters.
     *
     * This method implements a fluent interface.
     *
     * @param array<string,mixed> $parameters The parameters
     * @param int                 $type
     *
     * @return RouteCollectorInterface
     */
    public function addParameters(array $parameters, int $type = self::TYPE_REQUIREMENT): self;

    /**
     * Add route group.
     *
     * @param array<string,mixed> $attributes
     * @param callable|string     $callable
     *
     * @return RouteGroupInterface
     */
    public function group(array $attributes, $callable): RouteGroupInterface;

    /**
     * Add route.
     *
     * @param string[]                       $methods Array of HTTP methods
     * @param string                         $pattern The route pattern
     * @param callable|Closure|object|string $handler The route callable
     *
     * @return RouteInterface
     */
    public function map(array $methods, string $pattern, $handler = null): RouteInterface;

    /**
     * Same as to map(); method.
     *
     * @param RouteInterface $route
     */
    public function setRoute(RouteInterface $route): void;

    /**
     * Dispatches a matched route response.
     *
     * Uses the composed router to match against the incoming request, and
     * injects the request passed to the handler with the `RouteResults` instance
     * returned (using the `RouteResults` class name as the attribute name).
     * If routing succeeds, injects the request passed to the handler with any
     * matched parameters as well.
     *
     * @param ServerRequestInterface $request
     *
     * @throws RouteNotFoundException
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}
