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

namespace Bartacus\Bundle\BartacusBundle\TypoScript;

use JMS\DiExtraBundle\Annotation as DI;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collects all classes which should be usable for TypoScript userFunc calls.
 *
 * @DI\Service("bartacus.typoscript.user_func_collector")
 */
class UserFuncCollector
{
    /**
     * The tagged services for userFunc calls.
     *
     * [class name => instance]
     *
     * @var array
     */
    protected $userFuncs = [];

    /**
     * @param string $className
     * @param object $instance
     */
    public function addUserFunc(string $className, $instance): void
    {
        $this->userFuncs[$className] = $instance;
    }

    /**
     * Loads all registered instances into the {@see GeneralUtility::makeInstance()} singleton cache.
     */
    public function loadUserFuncs(): void
    {
        $refl = new \ReflectionClass(GeneralUtility::class);
        $reflProp = $refl->getProperty('singletonInstances');
        $reflProp->setAccessible(true);

        $instances = $reflProp->getValue();
        $instances = array_merge($instances, $this->userFuncs);

        $reflProp->setValue(null, $instances);
    }
}
