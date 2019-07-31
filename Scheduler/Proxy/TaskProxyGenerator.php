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

namespace Bartacus\Bundle\BartacusBundle\Scheduler\Proxy;

use Bartacus\Bundle\BartacusBundle\Scheduler\Proxy\TaskProxy\ExecuteMethod;
use Bartacus\Bundle\BartacusBundle\Scheduler\Proxy\TaskProxy\OptionsProperty;
use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;
use ProxyManager\ProxyGenerator\Assertion\CanProxyAssertion;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ReflectionClass;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use Zend\Code\Generator\ClassGenerator;

class TaskProxyGenerator implements ProxyGeneratorInterface
{
    public function generate(ReflectionClass $originalClass, ClassGenerator $classGenerator, array $proxyOptions = []): void
    {
        CanProxyAssertion::assertClassCanBeProxied($originalClass, false);
        if (!$originalClass->isSubclassOf(TaskInterface::class)) {
            throw new \InvalidArgumentException(\sprintf('The class "%s" must implement interface "%s" to generate a task proxy class', $originalClass->getName(), TaskInterface::class));
        }

        $classGenerator->addUse(AbstractTask::class);
        $classGenerator->setExtendedClass(AbstractTask::class);

        $classGenerator->addPropertyFromGenerator(new OptionsProperty());
        $classGenerator->addMethodFromGenerator(new ExecuteMethod($originalClass, $classGenerator));
    }
}
