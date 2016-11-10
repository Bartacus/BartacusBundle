<?php

/*
 * This file is part of the BartacusBundle.
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

declare(strict_types=1);

namespace Bartacus\Bundle\BartacusBundle;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\TypoScriptUserFuncPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BartacusBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->container->get('bartacus.typoscript.user_func_collector')->loadUserFuncs();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TypoScriptUserFuncPass());
    }
}
