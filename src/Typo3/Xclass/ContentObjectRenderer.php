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

namespace Bartacus\Bundle\BartacusBundle\Typo3\Xclass;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer as BaseContentObjectRenderer;

/**
 * XCLASS the content object renderer to make userFunc container aware
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class ContentObjectRenderer extends BaseContentObjectRenderer
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Be aware if the base class gets a constructor in future..
     */
    public function __construct()
    {
        $this->container = $GLOBALS['container'];
    }


    /**
     * Allows userFunc calls to services from the container
     *
     * {@inheritDoc}
     */
    public function callUserFunction($funcName, $conf, $content)
    {
        $parts = explode('->', $funcName);
        if (2 === count($parts) && $this->container->has($parts[0])) {
            $instance = $this->container->get($parts[0]);
            if (method_exists($instance, $parts[1])) {
                return call_user_func_array(
                    [$instance, $parts[1]],
                    [$content, $conf]
                );
            }
        }

        return parent::callUserFunction($funcName, $conf, $content);
    }

}
