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

namespace Bartacus\Bundle\BartacusBundle\EventSubscriber;

use Bartacus\Bundle\BartacusBundle\ConfigEvents;
use Bartacus\Bundle\BartacusBundle\Event\RequestMiddlewaresEvent;
use Bartacus\Bundle\BartacusBundle\Middleware\PrepareContentElementRenderer;
use Bartacus\Bundle\BartacusBundle\Middleware\SymfonyRouteResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BartacusRequestMiddlewaresSubscriber implements EventSubscriberInterface
{
    public function loadMiddlewares(RequestMiddlewaresEvent $event): void
    {
        $middlewares = [
            'frontend' => [
                'bartacus/symfony-route-resolver' => [
                    'target' => SymfonyRouteResolver::class,
                    'after' => [
                        'typo3/cms-frontend/static-route-resolver',
                        'typo3/cms-redirects/redirecthandler',
                    ],
                    'before' => [
                        'typo3/cms-frontend/base-redirect-resolver',
                        'typo3/cms-frontend/page-resolver',
                    ],
                ],
                'bartacus/prepare-content-element-renderer' => [
                    'target' => PrepareContentElementRenderer::class,
                    'after' => [
                        'typo3/cms-frontend/tsfe',
                        'typo3/cms-frontend/prepare-tsfe-rendering',
                    ],
                ],
            ],
        ];

        $event->addRequestMiddlewares($middlewares);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::REQUEST_MIDDLEWARES => [['loadMiddlewares', 8]],
        ];
    }
}
