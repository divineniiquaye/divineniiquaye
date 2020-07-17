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

namespace Flight\Routing\Tests;

use BiuradPHP\Http\Factories\GuzzleHttpPsr7Factory;
use Flight\Routing\Concerns\HttpMethods;
use Flight\Routing\Interfaces\RouterInterface;
use Flight\Routing\Services\DefaultFlightRouter;
use Flight\Routing\Services\SimpleRouteCompiler;
use Flight\Routing\Tests\Fixtures\SampleController;
use Generator;

class DefaultRouterTest extends RouterIntegrationTest
{
    public function getRouter(): RouterInterface
    {
        return new DefaultFlightRouter([new SimpleRouteCompiler(), 'compile']);
    }

    public function psrServerResponseFactory(): array
    {
        return [GuzzleHttpPsr7Factory::fromGlobalRequest(), new GuzzleHttpPsr7Factory()];
    }

    public function implicitRoutesAndRequests(): Generator
    {
        yield 'Root Route Text: get, callable'                  => [
            '/',
            '/',
            HttpMethods::METHOD_GET,
            function () {
                return 'Hello World';
            },
            [],
            ['content-type' => 'text/plain; charset=utf-8'],
        ];

        yield 'Root Route Html: get, callable'                  => [
            '/',
            '/',
            HttpMethods::METHOD_GET,
            function () {
                return '<html><body><h1>Hello World</h1></body></html>';
            },
            [
                'name'     => 'homepage',
                'generate' => [[], ['test' => 'fine']],
            ],
            [
                'generate'     => './?test=fine',
                'content-type' => 'text/html; charset=utf-8',
            ],
        ];

        yield 'Root Route XML: get, callable'                   => [
            '/',
            '/',
            HttpMethods::METHOD_GET,
            function () {
                return '<?xml version="1.0" encoding="UTF-8"?><route>Hello World</route>';
            },
            [],
            ['content-type' => 'application/xml; charset=utf-8'],
        ];

        yield 'Root Route JSON: get, callable'                  => [
            '/',
            '/',
            HttpMethods::METHOD_GET,
            function () {
                return new Helpers\DumpArrayTest();
            },
            [],
            ['content-type' => 'application/json'],
        ];

        yield 'Route Controller & Action: post, nullable'       => [
            '/test*<Flight\Routing\Tests\Fixtures\SampleController@homePageRequestResponse>',
            '/test',
            HttpMethods::METHOD_POST,
            null,
        ];

        yield 'Route Action: get, string'                       => [
            '/test*<homePageRequestResponse>',
            '/test',
            HttpMethods::METHOD_GET,
            SampleController::class,
        ];

        yield 'Route Action: get, object'                       => [
            '/test*<homePageRequestResponse>',
            '/test',
            HttpMethods::METHOD_GET,
            new SampleController(),
        ];

        yield 'Basic Route: get, callable'                      => [
            '/test',
            '/test',
            HttpMethods::METHOD_GET,
            function () {
                return 'Hello, this is a basic test route';
            },
        ];

        yield 'Basic Route Redirection: get, callable'          => [
            '/test',
            '/test/',
            HttpMethods::METHOD_GET,
            function () {
                return 'Hello, this is a basic test route';
            },
            [],
            ['status' => 302],
        ];

        yield 'Paramter Route: get, callable'                   => [
            '/test/{home}',
            '/test/cool',
            HttpMethods::METHOD_GET,
            function (string $home) {
                return 'Hello, this is a basic test route on subpage ' . $home;
            },
        ];

        yield 'Paramter & Default Route: get, callable'         => [
            '/test/{home}',
            '/test/cool',
            HttpMethods::METHOD_GET,
            function (string $home, int $id) {
                return $home . $id;
            },
            ['defaults' => ['id' => 233]],
            ['body'     => 'cool233'],
        ];

        yield 'Optional Paramter Route: get, callable'          => [
            '/test[/{home}]',
            '/test',
            HttpMethods::METHOD_GET,
            function (?string $home) {
                return 'Hello, this is a basic test route on subpage ' . $home;
            },
            [],
            ['body' => 'Hello, this is a basic test route on subpage '],
        ];

        yield 'Optional Paramter Route: path, get, callable'    => [
            '/test[/{home}]',
            '/test/cool',
            HttpMethods::METHOD_GET,
            function (?string $home) {
                return 'Hello, this is a basic test route on subpage ' . $home;
            },
            [],
            ['body' => 'Hello, this is a basic test route on subpage cool'],
        ];

        yield 'Route Domain: get, callable'                     => [
            '//example.com/test',
            '/test',
            HttpMethods::METHOD_GET,
            function () {
                return 'Hello World';
            },
            ['domain' => 'example.com'],
        ];

        yield 'Route Domain Regex: get, callable'               => [
            '//{id:int}.example.com/test',
            '/test',
            HttpMethods::METHOD_GET,
            function () {
                return 'Hello World';
            },
            [
                'name'     => 'domainpage',
                'domain'   => '99.example.com',
                'generate' => [['id' => '23'], []],
            ],
            ['generate' => 'http://23.example.com/test'],
        ];

        yield 'Route Domain Regex & Scheme: get, callable'      => [
            'https://{id:int}.example.com/{action}',
            '/tests',
            HttpMethods::METHOD_GET,
            function (string $action) {
                return 'Hello World' . $action;
            },
            [
                'name'     => 'domain_scheme_page',
                'domain'   => '99.example.com',
                'scheme'   => 'https',
                'generate' => [['id' => '23', 'action' => 'okay'], []],
            ],
            [
                'scheme'   => 'https',
                'generate' => 'https://23.example.com/okay',
            ],
        ];

        yield 'Nested Optional Paramter Route 1: get, callable' => [
            '/[{action}/[{id}]]',
            '/test/',
            HttpMethods::METHOD_GET,
            function (?string $action) {
                return $action;
            },
            [],
            ['status' => 302],
        ];

        yield 'Nested Optional Paramter Route 2: get, callable' => [
            '/[{action}/[{id}]]',
            '/test/id',
            HttpMethods::METHOD_GET,
            function (?string $action) {
                return $action;
            },
            [
                'name'     => 'nested',
                'generate' => [['action' => 'yes_we_can'], []],
            ],
            [
                'status'   => 200,
                'generate' => './yes_we_can',
            ],
        ];

        yield 'Regex Paramter Route : get, callable'            => [
            '/user/{id:[0-9-]+}',
            'user/23',
            HttpMethods::METHOD_GET,
            function (int $id) {
                return $id;
            },
        ];

        yield 'Complex Paramter Route 1: get, callable'         => [
            '/[{lang:[a-z]{2}}/]hello',
            '/hello',
            HttpMethods::METHOD_GET,
            function (?string $lang) {
                return $lang;
            },
        ];

        yield 'Complex Paramter Route 2: get, callable'         => [
            '/[{lang:[a-z]{2}}/]{name}',
            '/en/download',
            HttpMethods::METHOD_GET,
            function (?string $lang, string $name) {
                return $lang . $name;
            },
        ];

        yield 'Complex Paramter Route 3: get, callable'         => [
            '[{lang:[a-z]{2}}[-{sublang}]/]{name}[/page-{page=<0>}]',
            '/download',
            HttpMethods::METHOD_GET,
            function (?string $lang, ?string $sublang, string $name, $page) {
                return $lang . '-' . $sublang . $name . $page;
            },
            [],
            ['body' => '-download0'],
        ];

        yield 'Complex Paramter Route 4: get, callable'         => [
            '[{lang:[a-z]{2}}[-{sublang}]/]{name}[/page-{page=<0>}]',
            '/en-us/download',
            HttpMethods::METHOD_GET,
            function (?string $lang, ?string $sublang, string $name, $page) {
                return $lang . '-' . $sublang . $name . $page;
            },
            [],
            ['body' => 'en-usdownload0'],
        ];

        yield 'Complex Paramter Route 5: get, callable'         => [
            '[{lang:[a-z]{2}}[-{sublang}]/]{name}[/page-{page=<0>}]',
            '/en-us/download/page-12',
            HttpMethods::METHOD_GET,
            function (?string $lang, ?string $sublang, string $name, $page) {
                return $lang . '-' . $sublang . $name . $page;
            },
            [],
            ['body' => 'en-usdownload12'],
        ];
    }
}
