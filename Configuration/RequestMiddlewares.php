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

use Bartacus\Bundle\BartacusBundle\ContentElement\Middleware\ContentElementRendererMiddleware;
use Bartacus\Bundle\BartacusBundle\Routing\SymfonyRouteResolverMiddleware;
use Bartacus\Bundle\BartacusBundle\StaticRoute\Middleware\StaticRouteMiddleware;

return [
    'frontend' => [
        'bartacus/content-element-renderer' => [
            'target' => ContentElementRendererMiddleware::class,
            'after' => [
                'typo3/cms-frontend/tsfe',
                'typo3/cms-frontend/prepare-tsfe-rendering',
            ],
        ],
        'bartacus/static-route-resolver' => [
            'target' => StaticRouteMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site-resolver',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
                'typo3/cms-frontend/static-route-resolver',
                'typo3/cms-frontend/page-resolver',
            ],
        ],
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
