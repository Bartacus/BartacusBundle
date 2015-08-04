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

/**
 * Holds the definitions ob user objects and functions for injecting into Typo3
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class UserObjAndFuncManager
{
    /**
     * The tagged services for user objects
     *
     * [service_id => service instance]
     *
     * @var array
     */
    protected $userObjs = [];

    /**
     * The tagged services for user functions with all it's public methods
     *
     * [service_id => [instance, methods[]]
     *
     * @var array
     */
    protected $userFuncs = [];

    /**
     * @param string $serviceId
     * @param object $instance
     */
    public function addUserObj($serviceId, $instance)
    {
        $this->userObjs[$serviceId] = $instance;
    }

    /**
     * @param string $serviceId
     * @param object $instance
     * @param array  $methods
     */
    public function addUserFunc($serviceId, $instance, array $methods)
    {
        $this->userFuncs[$serviceId] = [$instance, $methods];
    }

    /**
     * Generate all user objects into typo3
     */
    public function generateUserObjs()
    {
        foreach ($this->userObjs as $serviceId => $instance) {
            $GLOBALS['T3_VAR']['getUserObj'][$serviceId] = $instance;
        }
    }

    /**
     * Generate all user functions into typo3
     */
    public function generateUserFuncs()
    {
        foreach ($this->userFuncs as $serviceId => $map) {
            list($instance, $methods) = $map;

            foreach ($methods as $method) {
                $reference = $serviceId.'->'.$method;

                $GLOBALS['T3_VAR']['callUserFunction'][$reference]['obj'] = $instance;
                $GLOBALS['T3_VAR']['callUserFunction'][$reference]['method'] = $method;
            }
        }
    }
}
