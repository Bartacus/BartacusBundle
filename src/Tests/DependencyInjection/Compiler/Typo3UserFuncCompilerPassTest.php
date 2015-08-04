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

namespace Bartacus\Bundle\BartacusBundle\Tests\DependencyInjection\Compiler;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3UserFuncCompilerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Test for the Typo3UserObjCompilerPass
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class Typo3UserFuncCompilerPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Typo3UserFuncCompilerPass());
    }

    /**
     * @test
     */
    public function if_compiler_pass_collects_services_by_adding_method_calls_these_will_exist()
    {
        $userObjAndFuncManager = new Definition();
        $this->setDefinition(
            'typo3.user_obj_and_func_manager',
            $userObjAndFuncManager
        );

        $userFunc = new Definition();
        $userFunc->setClass(
            'Bartacus\\Bundle\\BartacusBundle\\Tests\\DependencyInjection\\Compiler\\UserFuncStub'
        );
        $userFunc->addTag('typo3.user_func');
        $this->setDefinition('acme_user_func', $userFunc);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'typo3.user_obj_and_func_manager',
            'addUserFunc',
            [
                'acme_user_func',
                new Reference('acme_user_func'),
                ['someMethod']
            ]
        );
    }
}

/**
 * Empty stub with empty method
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class UserFuncStub
{
    public function someMethod() {}
}
