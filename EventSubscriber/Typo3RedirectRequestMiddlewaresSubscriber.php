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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Typo3RedirectRequestMiddlewaresSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::REQUEST_MIDDLEWARES => [['loadMiddlewares', 16]],
        ];
    }

    public function loadMiddlewares(RequestMiddlewaresEvent $event): void
    {
        $frontendMiddlewares = require $this->projectDir.'/vendor/typo3/cms-frontend/Configuration/RequestMiddlewares.php';

        $middlewares = [
            'frontend' => [
                'typo3/cms-frontend/static-route-resolver' => \array_merge($frontendMiddlewares['frontend']['typo3/cms-frontend/static-route-resolver'], [
                    'after' => [
                        'typo3/cms-frontend/site-resolver',
                    ],
                    'before' => [
                        'typo3/cms-frontend/base-redirect-resolver',
                    ],
                ]),
                'typo3/cms-frontend/base-redirect-resolver' => \array_merge($frontendMiddlewares['frontend']['typo3/cms-frontend/base-redirect-resolver'], [
                    'after' => [
                        'typo3/cms-frontend/static-route-resolver',
                    ],
                    'before' => [
                        'typo3/cms-frontend/page-resolver',
                    ],
                ]),
            ],
        ];

        $event->addRequestMiddlewares($middlewares);
    }
}
