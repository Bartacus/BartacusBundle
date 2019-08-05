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

namespace Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler;

use Bartacus\Bundle\BartacusBundle\Typo3\MakeInstanceServiceLocator;
use Bartacus\Bundle\BartacusBundle\Typo3\SymfonyServiceForMakeInstanceLoader;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class SymfonyServiceForMakeInstancePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(SymfonyServiceForMakeInstanceLoader::class) || !$container->has(MakeInstanceServiceLocator::class)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds('bartacus.make_instance');

        $classNames = [];
        $locatableServices = [];

        foreach ($taggedServices as $id => $tags) {
            $taggedDefinition = $container->findDefinition($id);
            $class = $taggedDefinition->getClass();

            if (!$r = $container->getReflectionClass($class)) {
                throw new InvalidArgumentException(\sprintf('Class "%s" used for service "%s" cannot be found.', $class, $id));
            }

            $class = $r->name;

            foreach ($tags as $attributes) {
                $locatableClass = $class;
                if (isset($attributes['alias'])) {
                    $locatableClass = $attributes['alias'];

                    if (!$container->getReflectionClass($locatableClass) || !\interface_exists($locatableClass)) {
                        if (\interface_exists($locatableClass)) {
                            $container->addResource(new ClassExistenceResource($locatableClass, false));
                        }

                        throw new InvalidArgumentException(\sprintf('Class or interface "%s" used for service "%s" as alias cannot be found.', $locatableClass, $id));
                    }
                }

                $classNames[] = $locatableClass;
                $locatableServices[$locatableClass] = new Reference($id);
            }
        }

        $container->findDefinition(SymfonyServiceForMakeInstanceLoader::class)->replaceArgument(0, $classNames);
        $container->findDefinition(MakeInstanceServiceLocator::class)->addArgument(ServiceLocatorTagPass::register($container, $locatableServices));
    }
}
