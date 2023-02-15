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

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collects all classes which should be usable for {@see GeneralUtility::makeInstance()} calls.
 */
final class SymfonyServiceForMakeInstanceLoader
{
    /**
     * @var string[]
     */
    private array $classNames;
    private MakeInstanceServiceLocator $serviceLocator;

    public function __construct(array $classNames, MakeInstanceServiceLocator $serviceLocator)
    {
        $this->classNames = $classNames;
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Loads all registered instances into the {@see GeneralUtility::makeInstance()} singleton cache.
     *
     * @throws \ReflectionException
     */
    public function load(): void
    {
        $reflectionClass = new \ReflectionClass(GeneralUtility::class);

        $this->loadClassNameCache($reflectionClass);
        $this->loadInstanceCache($reflectionClass);
    }

    /**
     * @throws \ReflectionException
     */
    private function loadClassNameCache(\ReflectionClass $reflectionClass): void
    {
        $reflectionProp = $reflectionClass->getProperty('finalClassNameCache');
        $reflectionProp->setAccessible(true);

        $classes = \array_combine($this->classNames, $this->classNames);

        $classNames = $reflectionProp->getValue();
        $classNames = \array_merge($classNames, $classes);

        $reflectionProp->setValue(null, $classNames);
    }

    /**
     * @throws \ReflectionException
     */
    private function loadInstanceCache(\ReflectionClass $reflectionClass): void
    {
        $reflectionProp = $reflectionClass->getProperty('singletonInstances');
        $reflectionProp->setAccessible(true);
        $reflectionProp->setValue(null, $this->serviceLocator);
    }
}
