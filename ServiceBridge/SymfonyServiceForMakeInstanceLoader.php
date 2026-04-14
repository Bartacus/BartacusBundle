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

namespace Bartacus\Bundle\BartacusBundle\ServiceBridge;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collects all classes which should be usable for {@see GeneralUtility::makeInstance()} calls.
 */
final class SymfonyServiceForMakeInstanceLoader
{
    public function __construct(
        private readonly array $classNames,
        private readonly MakeInstanceServiceLocator $serviceLocator,
    ) {
    }

    /**
     * Loads all registered instances into the {@see GeneralUtility::makeInstance()} singleton cache.
     *
     * @throws \ReflectionException
     */
    public function load(): void
    {
        $reflectionClass = new \ReflectionClass(GeneralUtility::class);

        $this->injectBartacusMakeInstanceClasses($reflectionClass);
        $this->injectServiceLocatorToSingletonInstances($reflectionClass);
    }

    /**
     * Manipulates the 'GeneralUtility::finalClassNameCache' property and adds all services tagged
     * with 'bartacus.make_instance' to the list.
     *
     * @throws \ReflectionException
     */
    private function injectBartacusMakeInstanceClasses(\ReflectionClass $reflectionClass): void
    {
        $reflectionProp = $reflectionClass->getProperty('finalClassNameCache');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionProp->setAccessible(true);

        $classes = \array_combine($this->classNames, $this->classNames);

        $classNames = $reflectionProp->getValue();
        $classNames = \array_merge($classNames, $classes);

        $reflectionProp->setValue(null, $classNames);
    }

    /**
     * Manipulates the 'GeneralUtility::singletonInstances' property to use the serviceLocator instead of the plain array.
     *
     * @throws \ReflectionException
     */
    private function injectServiceLocatorToSingletonInstances(\ReflectionClass $reflectionClass): void
    {
        $reflectionProp = $reflectionClass->getProperty('singletonInstances');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $reflectionProp->setAccessible(true);
        $reflectionProp->setValue(null, $this->serviceLocator);
    }
}
