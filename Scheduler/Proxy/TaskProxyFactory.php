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

use ProxyManager\Configuration;
use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use Symfony\Component\Filesystem\Filesystem;

final class TaskProxyFactory extends AbstractBaseFactory
{
    /**
     * @var TaskProxyGenerator|null
     */
    private $generator;

    public static function createProxyConfiguration(string $targetDir): Configuration
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($targetDir);

        $proxyConfiguration = new Configuration();
        $proxyConfiguration->setProxiesNamespace('BartacusGeneratedTaskProxy');
        $proxyConfiguration->setProxiesTargetDir($targetDir);
        $proxyConfiguration->setGeneratorStrategy(new FileWriterGeneratorStrategy(new FileLocator($targetDir)));
        $proxyConfiguration->setClassNameInflector(new ClassNameInflector('BartacusGeneratedTaskProxy'));

        return $proxyConfiguration;
    }

    /**
     * Generate a proxy from a class name.
     *
     * @return string The proxy class name
     */
    public function createProxy(string $className, array $proxyOptions = []): string
    {
        return $this->generateProxy($className, $proxyOptions);
    }

    protected function getGenerator(): ProxyGeneratorInterface
    {
        return $this->generator ?: $this->generator = new TaskProxyGenerator();
    }
}
