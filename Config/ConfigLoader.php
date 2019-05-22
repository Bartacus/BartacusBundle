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

namespace Bartacus\Bundle\BartacusBundle\Config;

use Bartacus\Bundle\BartacusBundle\ContentElement\Loader\ContentElementConfigLoader;
use Bartacus\Bundle\BartacusBundle\Middleware\PrepareContentElementRenderer;
use Bartacus\Bundle\BartacusBundle\Middleware\SymfonyRouteResolver;

/**
 * Delegating central config loader called on various places within TYPO3
 * to load and configure specific parts of the system.
 */
class ConfigLoader
{
    /**
     * @var ContentElementConfigLoader
     */
    protected $contentElement;

    public function __construct(ContentElementConfigLoader $contentElement)
    {
        $this->contentElement = $contentElement;
    }

    public function loadFromAdditionalConfiguration(): void
    {
        $this->contentElement->load();
    }

    public function loadFromRequestMiddlewares(): array
    {
        return [
            'frontend' => [
                'bartacus/symfony-route-resolver' => [
                    'target' => SymfonyRouteResolver::class,
                    'after' => [
                        'typo3/cms-frontend/authentication',
                        'typo3/cms-frontend/backend-user-authentication',
                        'typo3/cms-frontend/tsfe',
                        'typo3/cms-frontend/site',
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
                        'typo3/cms-frontend/site',
                        'typo3/cms-frontend/page-resolver',
                        'typo3/cms-frontend/page-argument-validator',
                        'typo3/cms-frontend/prepare-tsfe-rendering',
                    ],
                ],
            ],
        ];
    }
}
