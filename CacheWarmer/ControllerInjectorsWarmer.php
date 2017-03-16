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

namespace Bartacus\Bundle\BartacusBundle\CacheWarmer;

use JMS\DiExtraBundle\HttpKernel\ControllerResolver;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ControllerInjectorsWarmer implements CacheWarmerInterface
{
    private $kernel;

    private $controllerResolver;

    private $blackListedControllerFiles;

    public function __construct(KernelInterface $kernel, ControllerResolver $resolver, array $blackListedControllerFiles)
    {
        $this->kernel = $kernel;
        $this->controllerResolver = $resolver;
        $this->blackListedControllerFiles = $blackListedControllerFiles;
    }

    public function warmUp($cacheDir): void
    {
        // This avoids class-being-declared twice errors when the cache:clear
        // command is called. The controllers are not pre-generated in that case.
        $suffix = defined('Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate::NEW_CACHE_FOLDER_SUFFIX')
            ? CacheWarmerAggregate::NEW_CACHE_FOLDER_SUFFIX
            : '_new';

        if (basename($cacheDir) === $this->kernel->getEnvironment().$suffix) {
            return;
        }

        $classes = $this->findControllerClasses();
        foreach ($classes as $class) {
            $this->controllerResolver->createInjector($class);
        }
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function findControllerClasses(): array
    {
        $dirs = [];
        foreach ($this->kernel->getBundles() as $bundle) {
            if (!is_dir($controllerDir = $bundle->getPath().'/Controller')) {
                continue;
            }

            $dirs[] = $controllerDir;
        }

        foreach (Finder::create()->name('*Controller.php')->in($dirs)->files() as $file) {
            $filename = $file->getRealPath();
            if (!in_array($filename, $this->blackListedControllerFiles, true)) {
                require_once $filename;
            }
        }

        // It is not so important if these controllers never can be reached with
        // the current configuration nor whether they are actually controllers.
        // Important is only that we do not miss any classes.
        return array_filter(get_declared_classes(), function (string $name) {
            return preg_match('/(?<!TYPO3\\\CMS\\\Frontend\\\)Controller\\\(.+)Controller$/', $name) > 0;
        });
    }
}
