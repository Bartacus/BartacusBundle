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

namespace Bartacus\Bundle\BartacusBundle;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\NopCompilerPass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3UserFuncCompilerPass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3UserObjCompilerPass;
use Bartacus\Bundle\BartacusBundle\Typo3\UserObjAndFuncManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The bundle!
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'] = [
            'className' => 'Bartacus\\Bundle\\BartacusBundle\\Typo3\\Xclass\\ContentObjectRenderer'
        ];
        /** @var UserObjAndFuncManager $userObjAndFuncManager */
        $userObjAndFuncManager = $this->container->get(
            'typo3.user_obj_and_func_manager'
        );

        $userObjAndFuncManager->generateUserObjs();
        $userObjAndFuncManager->generateUserFuncs();
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new NopCompilerPass());
        $container->addCompilerPass(new Typo3UserObjCompilerPass());
        $container->addCompilerPass(new Typo3UserFuncCompilerPass());
    }
}
