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

/**
 * @Route("/prefix", name="do.", domain="biurad.com")
 */
class RouteWithPrefixController
{
    /**
     * @Route("/path", methods={"GET", "POST"}, name="action")
     */
    public function action()
    {
    }

    /**
     * @Route("/path_two", methods={"GET", "POST"}, name="action_two")
     */
    public function actionTwo()
    {
    }
}
