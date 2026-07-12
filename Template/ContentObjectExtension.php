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
 * along with this bundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\Template;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ContentObjectExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        return [
            new TwigFunction('bartacus_cobject', [ContentObjectRuntime::class, 'cObject'], ['is_safe' => ['html']]),
        ];
    }
}
