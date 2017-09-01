<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) 2016-2017 Patrik Karisch
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

namespace Bartacus\Bundle\BartacusBundle\Bootstrap;

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpKernel\Kernel;
use TYPO3\CMS\Core\Compatibility\LoadedExtensionArrayElement;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;

final class SymfonyBootstrap
{
    /**
     * @var Kernel
     */
    private static $kernel;

    public static function getKernel(): Kernel
    {
        return self::$kernel;
    }

    public static function initKernel(): void
    {
        \defined('SYMFONY_ENV') || \define('SYMFONY_ENV', \getenv('SYMFONY_ENV') ?: 'prod');
        \defined('SYMFONY_DEBUG') || \define('SYMFONY_DEBUG', \filter_var(\getenv('SYMFONY_DEBUG') ?: \SYMFONY_ENV === 'dev', \FILTER_VALIDATE_BOOLEAN));

        if (\SYMFONY_DEBUG) {
            Debug::enable();
        }

        self::$kernel = new \AppKernel(\SYMFONY_ENV, \SYMFONY_DEBUG);
        self::$kernel->boot();

        // deprecated, will be removed in 2.0, use SymfonyBootstrap::getKernel() instead.
        $GLOBALS['kernel'] = self::$kernel;
    }

    public static function initAppPackage(): void
    {
        /** @var PackageManager $packageManager */
        $packageManager = Bootstrap::getInstance()->getEarlyInstance(PackageManager::class);
        $package = new Package($packageManager, 'app', \rtrim(\realpath(self::$kernel->getProjectDir().'/app/'), '\\/').'/');
        $packageManager->registerPackage($package);

        \Closure::bind(function (PackageManager $instance) use ($package) {
            $instance->runtimeActivatedPackages[$package->getPackageKey()] = $package;
        }, $packageManager, PackageManager::class)($packageManager);

        if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()])) {
            $loadedExtArrayElement = new LoadedExtensionArrayElement($package);
            \Closure::bind(function (LoadedExtensionArrayElement $instance) {
                $instance->extensionInformation['type'] = 'L';
            }, $loadedExtArrayElement, LoadedExtensionArrayElement::class)($loadedExtArrayElement);

            $GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()] = $loadedExtArrayElement->toArray();
        }
    }
}
