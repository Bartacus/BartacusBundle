<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) Emily Karisch
 *
 * The BartacusBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The BartacusBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the BartacusBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\StaticRoute\Middleware;

use Bartacus\Bundle\BartacusBundle\StaticRoute\Event\StaticRouteEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;

class StaticRouteMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');

        if ($site instanceof Site) {
            $route = ltrim($request->getUri()->getPath(), '/');

            if ($route) {
                $event = new StaticRouteEvent($request, $site, $route);
                $this->dispatcher->dispatch($event, StaticRouteEvent::EVENT_NAME);
                $response = $event->getResponse();

                if ($response instanceof Response) {
                    return $response;
                }
            }
        }

        return $handler->handle($request);
    }
}
