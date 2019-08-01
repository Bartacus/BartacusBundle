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

namespace Bartacus\Bundle\BartacusBundle\Scheduler\Proxy\TaskProxy;

use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use ProxyManager\Generator\MethodGenerator;
use ReflectionClass;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use Zend\Code\Generator\ClassGenerator;

class GetProgressMethod extends MethodGenerator
{
    /**
     * @var string
     */
    private $methodTemplate = <<<'PHP'
$task = SymfonyBootstrap::getKernel()->getContainer()->get(%s::class);

return $task->getProgress($this->options);
PHP;

    public function __construct(ReflectionClass $originalClass, ClassGenerator $classGenerator)
    {
        parent::__construct('getProgress');

        $this->setVisibility(self::VISIBILITY_PUBLIC);
        $this->setDocBlock('Gets the progress of the proxied task.');
        $this->setReturnType('float');

        $classGenerator->addUse(ProgressProviderInterface::class);
        $classGenerator->addUse(SymfonyBootstrap::class);
        $classGenerator->addUse($originalClass->getName());

        $classGenerator->setImplementedInterfaces(\array_merge(
            $classGenerator->getImplementedInterfaces(),
            [ProgressProviderInterface::class]
        ));

        $this->setBody(\sprintf(
            $this->methodTemplate,
            $originalClass->getShortName()
        ));
    }
}
