<?php

/*
 * This file is part of the Bartacus project.
 *
 * Copyright (c) 2015 Patrik Karisch, pixelart GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Base class for transforming a Typo3 extension into a Symfony bundle.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class Typo3Extension extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if (null === $this->path) {
            $reflected = new \ReflectionObject($this);
            $this->path = dirname(dirname($reflected->getFileName()));
        }

        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $application)
    {
        $oldPath = $this->getPath();
        $this->path = $oldPath.'/Classes';

        parent::registerCommands($application);

        $this->path = $oldPath;
    }
}
