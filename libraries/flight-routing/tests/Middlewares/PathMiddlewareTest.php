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

namespace Flight\Routing\Tests\Middlewares;

use Flight\Routing\Middlewares\PathMiddleware;
use Flight\Routing\Route;
use Flight\Routing\RoutePipeline;
use Flight\Routing\Tests\BaseTestCase;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PathMiddlewareTest
 */
class PathMiddlewareTest extends BaseTestCase
{
    public function testProcessStatus(): void
    {
        $router   = $this->getRouter();
        $pipeline = new RoutePipeline();

        $pipeline->addMiddleware(new PathMiddleware());
        $router->addRoute(new Route('path_middleware_200', ['GET'], '/foo', [$this, 'handlePath']));

        $response = $pipeline->process(new ServerRequest('GET', 'foo'), $router);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('foo', $response->getHeaderLine('Expected'));
    }

    /**
     * @dataProvider pathCombinationsData
     *
     * @param string $uriPath
     * @param string $expectedPath
     * @param bool   $expectsStatus
     */
    public function testProcess(string $uriPath, string $expectedPath, bool $expectsStatus): void
    {
        $router   = $this->getRouter();
        $pipeline = new RoutePipeline();

        $pipeline->addMiddleware(new PathMiddleware());
        $router->addRoute(new Route('path_middleware', ['GET', 'POST'], $uriPath, [$this, 'handlePath']));

        $response = $pipeline->process(new ServerRequest('GET', $expectedPath), $router);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($expectsStatus ? 301 : 302, $response->getStatusCode());
        $this->assertEquals($expectedPath, $response->getHeaderLine('Expected'));

        $response = $pipeline->process(new ServerRequest('POST', $expectedPath), $router);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($expectsStatus ? 308 : 307, $response->getStatusCode());
        $this->assertEquals($expectedPath, $response->getHeaderLine('Expected'));
    }

    public function handlePath(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response->withHeader('Expected', $request->getUri()->getPath());
    }

    /**
     * @return string[]
     */
    public function pathCombinationsData(): array
    {
        return [
            // name                      => [$uriPath, $expectedPath,   $expectsStatus ]
            'prefix-bare-bare'           => ['/foo',   'foo/',          true],
            'root-prefix-tail'           => ['/foo',   '/foo/',         true],
            'prefix-surround-tail'       => ['/foo/',  '/foo',         true],
        ];
    }
}
