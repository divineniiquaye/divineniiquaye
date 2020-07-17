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

namespace Flight\Routing\Services;

use ArrayIterator;
use CachingIterator;
use Closure;
use Flight\Routing\Concerns\RouteValidation;
use Flight\Routing\Interfaces\RouteInterface;
use Flight\Routing\Interfaces\RouterInterface;
use Flight\Routing\RouteResults;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

class DefaultFlightRouter implements RouterInterface
{
    use RouteValidation;

    private const URI_FIXERS = [
        '[]'  => '',
        '[/]' => '',
        '['   => '',
        ']'   => '',
        '://' => '://',
        '//'  => '/',
    ];

    /**
     * Symfony RouteCompiler.
     *
     * @var SimpleRouteCompiler
     */
    private $compiler;

    /**
     * Routes to inject into the underlying RouteCollector.
     *
     * @var RouteInterface[]
     */
    private $routesToInject = [];

    /**
     * Constructor.
     *
     * Accepts optionally a Compiler callable similar to
     * Flight\Routing\Services\SimpleRouteCompiler having four
     * important methods.
     * - compile(); This generates the matching regex.
     * - getRegex(); The generated regex
     * - getRegexTemplate(); The template for generated regex
     * - getVariables(): The matched variables in regex.
     *
     * If either is not provided defaults will be used:
     * - A SimpleRouteCompiler instance will parse the routes and return
     *   the absolute matched route.
     *
     * @param null|callable $compiler if not provided, a default is used
     *                                implementation will be used
     */
    public function __construct(callable $compiler = null)
    {
        $this->compiler = $compiler ?? $this->createDispatcherCallback();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        foreach ($this->routesToInject as $name => $route) {
            $this->routesToInject[$name] = clone $route;
        }
    }

    /**
     * Add a route to the collection.
     *
     * @param RouteInterface $route
     */
    public function addRoute(RouteInterface $route): void
    {
        $this->routesToInject[] = $route;
    }

    /**
     * {@inheritdoc}
     */
    public function match(ServerRequestInterface $request): RouteResults
    {
        $domain = $request->getUri()->getHost();
        $scheme = $request->getUri()->getScheme();
        $method = $request->getMethod();

        $basePath = \dirname($request->getServerParams()['SCRIPT_NAME'] ?? '');

        // For phpunit testing to be smooth.
        if ('cli' === \PHP_SAPI) {
            $basePath = '';
        }

        $finalisedPath = \substr($request->getUri()->getPath(), \strlen($basePath));
        $matched       = $this->marshalMatchedRoute($method, $scheme, $domain, \rawurldecode($finalisedPath));

        // Get the request matching format.
        [$status, $parameters, $route] = $matched;
        $finalised                     = new RouteResults($status, $parameters, $route);

        // A feature adopted from Symfony routing, workaround fix.
        if (
            RouteResults::FOUND === $status &&
            null !== $redirectedPath = $this->compareRedirection($route->getPath(), $finalisedPath)
        ) {
            $finalised->shouldRedirect((\strlen($basePath) > 1 ? $basePath . '' : '/') . $redirectedPath);
        }

        return $finalised;
    }

    /**
     * Generate a URI based on a given route.
     *
     * Replacements are written as `{name}`, `{name:pattern}` or `{name=<default>}`;.
     * This method will automatedly search for the best route, then
     * match based on the available substitutions and generates a uri.
     *
     * {@inheritdoc}
     *
     * @return string URI path generated
     */
    public function generateUri(RouteInterface $route, array $substitutions = []): string
    {
        $match = ($this->compiler)($route);

        $parameters = \array_merge(
            $match->getVariables(),
            $route->getDefaults(),
            $this->fetchOptions($substitutions, \array_keys($match->getVariables()))
        );

        //Uri without empty blocks (pretty stupid implementation)
        if ($path = $this->interpolate($match->getRegexTemplate() ?? '', $parameters)) {
            $path = \sprintf(
                '%s://%s/',
                $route->getSchemes() ? \current($route->getSchemes()) : 'http',
                \trim($path, '.')
            );
        }

        return $path .= $this->interpolate($match->getRegexTemplate(false), $parameters); // Return generated path
    }

    /**
     * Gets the current RouterInterface as an Iterator that includes all routes.
     *
     * It implements IteratorAggregate.
     *
     * @return CachingIterator An \ArrayIterator object for iterating over routes
     */
    public function getIterator()
    {
        $iterator = new ArrayIterator($this->routesToInject);

        return new CachingIterator($iterator, CachingIterator::FULL_CACHE);
    }

    /**
     * Gets the number of Routes in this collection.
     *
     * @return int The number of routes
     */
    public function count(): int
    {
        return \count($this->routesToInject);
    }

    /**
     * Marshals a route result based on the results of matching URL from set of routes.
     *
     * @param string $method The current request method
     * @param string $scheme The current uri scheme
     * @param string $host   The domain to be parsed
     * @param string $path   The path info to be parsed
     *
     * @return array an array of results
     */
    private function marshalMatchedRoute(string $method, string $scheme, string $host, string $path): array
    {
        $path = \trim($path, '/') ?: '/';

        foreach ($this as $index => $route) {
            // Let's match the routes
            $match                         = ($this->compiler)($route);
            [$parameters, $HostParameters] = [[], []];

            if (
                $this->compareDomain($match->getHostRegex(), $host, $HostParameters) &&
                $this->compareUri($match->getRegex(), $path, $parameters) &&
                $this->compareScheme($route->getSchemes(), $scheme)
            ) {
                // Throw and exception if url is not found no request method.
                if (!$this->compareMethod($route->getMethods(), $method)) {
                    return [RouteResults::METHOD_NOT_ALLOWED, [], $route];
                }
                $arguments = $this->fetchOptions(\array_replace($parameters, $HostParameters), $match->getVariables());

                if (empty($arguments)) {
                    $arguments = $match->getVariables();
                }

                return [RouteResults::FOUND, $this->mergeDefaults($arguments, $route->getDefaults()), $route];
            }
        }

        return [RouteResults::NOT_FOUND, [], null];
    }

    /**
     * Return a default implementation of a callback that can return a Dispatcher.
     */
    private function createDispatcherCallback(): callable
    {
        return function (RouteInterface $route) {
            return (new SimpleRouteCompiler())->compile($route);
        };
    }

    /**
     * Interpolate string with given values.
     *
     * @param string $string
     * @param array  $values
     *
     * @return string
     */
    private function interpolate(string $string, array $values): string
    {
        $replaces = [];

        foreach ($values as $key => $value) {
            $value                = (\is_array($value) || $value instanceof Closure) ? '' : $value;
            $replaces["<{$key}>"] = \is_object($value) ? (string) $value : $value;
        }

        return \strtr($string, $replaces + self::URI_FIXERS);
    }

    /**
     * Fetch uri segments and query parameters.
     *
     * @param array|Traversable $parameters
     * @param array             $allowed
     *
     * @return array
     */
    private function fetchOptions($parameters, array $allowed): array
    {
        $result = [];

        foreach ($parameters as $key => $parameter) {
            if (\is_numeric($key) && isset($allowed[$key])) {
                // this segment fetched keys from given parameters either by name or by position
                $key = $allowed[$key];
            }

            //String must be normalized here
            if (\is_string($parameter) && !\preg_match('/^[a-z\-_0-9]+$/i', $parameter)) {
                $result[$key] = \htmlspecialchars((string) $parameter);

                continue;
            }

            $result[$key] = $parameter;
        }

        return $result;
    }
}
