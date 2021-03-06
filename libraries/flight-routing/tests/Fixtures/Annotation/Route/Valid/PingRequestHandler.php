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

namespace Flight\Routing\Tests\Fixtures\Annotation\Route\Valid;

use Flight\Routing\Annotation\Route;
use Flight\Routing\Tests\Fixtures\BlankRequestHandler;

/**
 * @Route(
 *   name="ping",
 *   path="/ping",
 *   methods={"HEAD", "GET"},
 *   middlewares={
 *     "Flight\Routing\Tests\Fixtures\BlankMiddleware",
 *     "Flight\Routing\Tests\Fixtures\BlankMiddleware"
 *   },
 *   defaults={
 *     "foo": "bar",
 *     "bar": "baz"
 *   }
 * )
 */
class PingRequestHandler extends BlankRequestHandler
{
}
