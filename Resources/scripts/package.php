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

use TYPO3\CMS\Core\Compatibility\LoadedExtensionArrayElement;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;

/** @var PackageManager $packageManager */
$packageManager = Bootstrap::getInstance()->getEarlyInstance(PackageManager::class);
$package = new Package($packageManager, 'app', \rtrim(\realpath(__DIR__.'/../../../../../app/'), '\\/').'/');
$packageManager->registerPackage($package);

Closure::bind(function (PackageManager $instance) use ($package) {
    $instance->runtimeActivatedPackages[$package->getPackageKey()] = $package;
}, $packageManager, PackageManager::class)->__invoke($packageManager);

if (!isset($GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()])) {
    $loadedExtArrayElement = new LoadedExtensionArrayElement($package);
    Closure::bind(function (LoadedExtensionArrayElement $instance) {
        $instance->extensionInformation['type'] = 'L';
    }, $loadedExtArrayElement, LoadedExtensionArrayElement::class)->__invoke($loadedExtArrayElement);

    $GLOBALS['TYPO3_LOADED_EXT'][$package->getPackageKey()] = $loadedExtArrayElement->toArray();
}
