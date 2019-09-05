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

use PackageVersions\Versions;
use ProxyManager\Configuration;
use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\Generator\ClassGenerator;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ProxyManager\Version;
use Symfony\Component\Filesystem\Filesystem;

final class TaskProxyFactory extends AbstractBaseFactory
{
    /**
     * Cached checked class names.
     *
     * @var string[]
     */
    private $checkedClasses = [];

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

    protected function generateProxy(string $className, array $proxyOptions = []): string
    {
        if (\array_key_exists($className, $this->checkedClasses)) {
            return $this->checkedClasses[$className];
        }

        $proxyParameters = [
            'className' => $className,
            'factory' => \get_class($this),
            'proxyManagerVersion' => Version::getVersion(),
            'bartacusVersion' => Versions::getVersion('bartacus/bartacus-bundle'),
            'interfaces' => (new \ReflectionClass($className))->getInterfaceNames(),
        ];

        $proxyClassName = $this->configuration
            ->getClassNameInflector()
            ->getProxyClassName($className, $proxyParameters)
        ;

        if (!\class_exists($proxyClassName)) {
            $this->generateProxyClass($proxyClassName, $className, $proxyParameters, $proxyOptions);
        }

        $this
            ->configuration
            ->getSignatureChecker()
            ->checkSignature(new \ReflectionClass($proxyClassName), $proxyParameters)
        ;

        return $this->checkedClasses[$className] = $proxyClassName;
    }

    /**
     * Generates the provided `$proxyClassName` from the given `$className` and `$proxyParameters`.
     */
    private function generateProxyClass(string $proxyClassName, string $className, array $proxyParameters, array $proxyOptions = []): void
    {
        $className = $this->configuration->getClassNameInflector()->getUserClassName($className);
        $phpClass = new ClassGenerator($proxyClassName);

        $this->getGenerator()->generate(new \ReflectionClass($className), $phpClass, $proxyOptions);

        $phpClass = $this->configuration->getClassSignatureGenerator()->addSignature($phpClass, $proxyParameters);
        $this->configuration->getGeneratorStrategy()->generate($phpClass, $proxyOptions);

        $autoloader = $this->configuration->getProxyAutoloader();
        $autoloader($proxyClassName);
    }
}
