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

namespace Bartacus\Bundle\BartacusBundle\Tests\DependencyInjection;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\BartacusExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

/**
 * Tests the bundle configuration
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusExtensionTest extends AbstractExtensionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getContainerExtensions()
    {
        return [new BartacusExtension()];
    }

    /**
     * @test
     */
    public function after_loading_the_correct_service_has_been_set()
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'typo3.user_obj_and_func_manager',
            'Bartacus\\Bundle\\BartacusBundle\\Typo3\\UserObjAndFuncManager'
        );
    }
}
