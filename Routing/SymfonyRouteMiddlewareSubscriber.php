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

namespace Bartacus\Bundle\BartacusBundle\Routing;

use Bartacus\Bundle\BartacusBundle\Config\Event\RequestMiddlewaresEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SymfonyRouteMiddlewareSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestMiddlewaresEvent::EVENT_NAME => [['loadMiddleware', 8]],
        ];
    }

    public function loadMiddleware(RequestMiddlewaresEvent $event): void
    {
        $middlewares = [
            'frontend' => [
                'bartacus/symfony-route-resolver' => [
                    'target' => SymfonyRouteResolverMiddleware::class,
                    'after' => [
                        'typo3/cms-redirects/redirecthandler',
                    ],
                    'before' => [
                        'typo3/cms-frontend/base-redirect-resolver',
                        'typo3/cms-frontend/page-resolver',
                    ],
                ],
            ],
        ];

        $event->addRequestMiddlewares($middlewares);
    }
}
