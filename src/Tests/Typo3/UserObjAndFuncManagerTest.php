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

namespace Bartacus\Bundle\BartacusBundle\Tests\Typo3;

use Bartacus\Bundle\BartacusBundle\Typo3\UserObjAndFuncManager;

/**
 * Tests the manager if correct values are inserted in the array
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class UserObjAndFuncManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_generates_correct_user_obj()
    {
        $stub = $this->getMockBuilder(
            'Bartacus\\Bundle\\BartacusBundle\\Tests\\Typo3\\UserObjStub'
        )->getMock();

        $manager = new UserObjAndFuncManager();
        $manager->addUserObj('acme.user_obj', $stub);
        $manager->generateUserObjs();

        $this->assertArrayHasKey(
            'acme.user_obj',
            $GLOBALS['T3_VAR']['getUserObj']
        );

        $this->assertInstanceOf(
            'Bartacus\\Bundle\\BartacusBundle\\Tests\\Typo3\\UserObjStub',
            $GLOBALS['T3_VAR']['getUserObj']['acme.user_obj']
        );
    }

    /**
     * @test
     */
    public function it_generates_correct_user_func()
    {
        $stub = $this->getMockBuilder(
            'Bartacus\\Bundle\\BartacusBundle\\Tests\\Typo3\\UserFuncStub'
        )->getMock();

        $manager = new UserObjAndFuncManager();
        $manager->addUserFunc('acme.user_obj', $stub, ['someMethod']);
        $manager->generateUserFuncs();

        $this->assertArrayHasKey(
            'acme.user_obj->someMethod',
            $GLOBALS['T3_VAR']['callUserFunction']
        );

        $this->assertInstanceOf(
            'Bartacus\\Bundle\\BartacusBundle\\Tests\\Typo3\\UserFuncStub',
            $GLOBALS['T3_VAR']['callUserFunction']['acme.user_obj->someMethod']['obj']
        );

        $this->assertEquals(
            'someMethod',
            $GLOBALS['T3_VAR']['callUserFunction']['acme.user_obj->someMethod']['method']
        );
    }
}
