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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface PublisherInterface
 * Publishers are responsible to publish the response provided by controllers.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface PublisherInterface
{
    /**
     * Publish the content.
     *
     * @param PsrResponseInterface|StreamInterface $content
     * @param null|EmitterInterface                $emitter
     *
     * @return bool
     */
    public function publish($content, ?EmitterInterface $emitter = null): bool;
}
