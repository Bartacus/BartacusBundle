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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service bridge to TYPO3 instantiation and global instances.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusTypo3Bridge
{
    /**
     * Wrapper around {@see GeneralUtility::makeInstance()} to call via service
     * container expression.
     *
     * @param $className
     *
     * @return object
     */
    public function makeInstance($className)
    {
        return GeneralUtility::makeInstance($className);
    }

    /**
     * Get a TYPO3 global into the service container.
     *
     * @param $global
     *
     * @return mixed
     */
    public function getGlobal($global)
    {
        if (!isset($GLOBALS[$global])) {
            throw new \InvalidArgumentException(sprintf(
                'The global %s does not exist.',
                $global
            ));
        }

        return $GLOBALS[$global];
    }
}
