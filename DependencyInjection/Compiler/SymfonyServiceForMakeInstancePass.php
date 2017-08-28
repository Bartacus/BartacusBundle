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

namespace Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler;

use Bartacus\Bundle\BartacusBundle\Typo3\SymfonyServiceForMakeInstanceLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SymfonyServiceForMakeInstancePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(SymfonyServiceForMakeInstanceLoader::class)) {
            return;
        }

        $definition = $container->findDefinition(SymfonyServiceForMakeInstanceLoader::class);

        $taggedServices = $container->findTaggedServiceIds('bartacus.typoscript');

        $taggedServices = \array_merge(
            $taggedServices,
            $container->findTaggedServiceIds('bartacus.make_instance')
        );

        foreach ($taggedServices as $id => $tags) {
            $taggedDefinition = $container->findDefinition($id);
            $taggedDefinition->setLazy(true);

            $definition->addMethodCall(
                'addService',
                [$taggedDefinition->getClass(), new Reference($id)]
            );
        }
    }
}
