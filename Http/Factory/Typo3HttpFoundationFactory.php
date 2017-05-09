<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) 2016-2017 Patrik Karisch
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

namespace Bartacus\Bundle\BartacusBundle\Http\Factory;

use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Filesystem\Filesystem;

class Typo3HttpFoundationFactory extends HttpFoundationFactory
{
    /**
     * Gets a temporary file path within TYPO3, because they
     * enforce uploaded files to be moved within path_SITE,
     * breaking the interface.
     */
    protected function getTemporaryPath(): string
    {
        $path = \PATH_site.'typo3temp/var/uploads';

        $fs = new Filesystem();
        $fs->mkdir($path, 0770);

        return \tempnam($path, \uniqid('symfony', true));
    }
}
