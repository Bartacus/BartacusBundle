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

namespace Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Find all typo3.user_obj tagged services and add them to the manager
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class Typo3UserFuncCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('typo3.user_obj_and_func_manager')) {
            return;
        }

        $definition = $container->findDefinition(
            'typo3.user_obj_and_func_manager'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'typo3.user_func'
        );

        foreach ($taggedServices as $id => $tags) {
            $taggedDefinition = $container->findDefinition($id);
            $reflectionObject = new \ReflectionClass(
                $taggedDefinition->getClass()
            );

            $methods = $reflectionObject->getMethods(
                \ReflectionMethod::IS_PUBLIC
            );

            $methods = array_map(
                function ($method) {
                    /** @var \ReflectionMethod $method */
                    return $method->getName();
                },
                $methods
            );

            $definition->addMethodCall(
                'addUserFunc',
                [$id, new Reference($id), $methods]
            );
        }
    }
}
