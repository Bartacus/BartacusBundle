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

namespace Bartacus\Bundle\BartacusBundle;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\ContentElementAttributePass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\SymfonyServiceForMakeInstancePass;
use Bartacus\Bundle\BartacusBundle\Typo3\SymfonyServiceForMakeInstanceLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BartacusBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->container->get(SymfonyServiceForMakeInstanceLoader::class)?->load();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SymfonyServiceForMakeInstancePass());
        $container->addCompilerPass(new ContentElementAttributePass());
    }
}
