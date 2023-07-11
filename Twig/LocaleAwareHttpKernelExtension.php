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

namespace Bartacus\Bundle\BartacusBundle\Twig;

use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class LocaleAwareHttpKernelExtension extends AbstractExtension implements RequestContextAwareInterface
{
    private ?RequestContext $context = null;

    public function getFunctions(): array
    {
        return [
            new TwigFunction('controller', [$this, 'controller']),
        ];
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function controller($controller, array $attributes = [], array $query = []): ControllerReference
    {
        $locale = $this->context->getParameter('_locale');

        if ($locale && !\array_key_exists('_locale', $attributes)) {
            $attributes['_locale'] = $locale;
        }

        return new ControllerReference($controller, $attributes, $query);
    }
}
