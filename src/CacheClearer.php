<?php

/*
 * This file is part of the Bartacus project.
 *
 * Copyright (c) 2015 - 2016 Patrik Karisch, pixelart GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service for clearing and warming up the cache from TYPO3.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class CacheClearer extends ContainerAware
{
    /**
     * @param array $params parameter array
     *
     * @return void
     */
    public function clear(&$params)
    {
        if (in_array($params['cacheCmd'], ['system', 'all'], true)) {
            $realCacheDir = $this->getContainer()->getParameter('kernel.cache_dir');
            // the old cache dir name must not be longer than the real one to avoid exceeding
            // the maximum length of a directory or file path within it (esp. Windows MAX_PATH)
            $oldCacheDir = substr($realCacheDir, 0, -1).('~' === substr($realCacheDir, -1) ? '+' : '~');
            $filesystem = $this->getContainer()->get('filesystem');

            if (!is_writable($realCacheDir)) {
                throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $realCacheDir));
            }

            if ($filesystem->exists($oldCacheDir)) {
                $filesystem->remove($oldCacheDir);
            }

            $this->getContainer()->get('cache_clearer')->clear($realCacheDir);

            // the warmup cache dir name must have the same length than the real one
            // to avoid the many problems in serialized resources files
            $realCacheDir = realpath($realCacheDir);
            $warmupDir = substr($realCacheDir, 0, -1).'_';

            if ($filesystem->exists($warmupDir)) {
                $filesystem->remove($warmupDir);
            }

            $this->warmup($warmupDir, $realCacheDir, false);

            $filesystem->rename($realCacheDir, $oldCacheDir);
            if ('\\' === DIRECTORY_SEPARATOR) {
                sleep(1);  // workaround for Windows PHP rename bug
            }
            $filesystem->rename($warmupDir, $realCacheDir);

            $filesystem->remove($oldCacheDir);
        }
    }

    /**
     * @param string $warmupDir
     * @param string $realCacheDir
     * @param bool   $enableOptionalWarmers
     */
    protected function warmup($warmupDir, $realCacheDir, $enableOptionalWarmers = true)
    {
        // create a temporary kernel
        /** @var KernelInterface $realKernel */
        $realKernel = $this->getContainer()->get('kernel');
        $realKernelClass = get_class($realKernel);
        $namespace = '';
        if (false !== $pos = strrpos($realKernelClass, '\\')) {
            $namespace = substr($realKernelClass, 0, $pos);
            $realKernelClass = substr($realKernelClass, $pos + 1);
        }
        $tempKernel = $this->getTempKernel($realKernel, $namespace, $realKernelClass, $warmupDir);
        $tempKernel->boot();

        $tempKernelReflection = new \ReflectionObject($tempKernel);
        $tempKernelFile = $tempKernelReflection->getFileName();

        // warmup temporary dir
        $warmer = $tempKernel->getContainer()->get('cache_warmer');
        if ($enableOptionalWarmers) {
            $warmer->enableOptionalWarmers();
        }
        $warmer->warmUp($warmupDir);

        // fix references to the Kernel in .meta files
        $safeTempKernel = str_replace('\\', '\\\\', get_class($tempKernel));
        $realKernelFQN = get_class($realKernel);

        foreach (Finder::create()->files()->name('*.meta')->in($warmupDir) as $file) {
            file_put_contents($file, preg_replace(
                '/(C\:\d+\:)"'.$safeTempKernel.'"/',
                sprintf('$1"%s"', $realKernelFQN),
                file_get_contents($file)
            ));
        }

        // fix references to cached files with the real cache directory name
        $search = [$warmupDir, str_replace('\\', '\\\\', $warmupDir)];
        $replace = str_replace('\\', '/', $realCacheDir);
        foreach (Finder::create()->files()->in($warmupDir) as $file) {
            $content = str_replace($search, $replace, file_get_contents($file));
            file_put_contents($file, $content);
        }

        // fix references to kernel/container related classes
        $fileSearch = $tempKernel->getName().ucfirst($tempKernel->getEnvironment()).'*';
        $search = [
            $tempKernel->getName().ucfirst($tempKernel->getEnvironment()),
            sprintf('\'kernel.name\' => \'%s\'', $tempKernel->getName()),
            sprintf('key="kernel.name">%s<', $tempKernel->getName()),
        ];
        $replace = [
            $realKernel->getName().ucfirst($realKernel->getEnvironment()),
            sprintf('\'kernel.name\' => \'%s\'', $realKernel->getName()),
            sprintf('key="kernel.name">%s<', $realKernel->getName()),
        ];
        foreach (Finder::create()->files()->name($fileSearch)->in($warmupDir) as $file) {
            $content = str_replace($search, $replace, file_get_contents($file));
            file_put_contents(str_replace($search, $replace, $file), $content);
            unlink($file);
        }

        // remove temp kernel file after cache warmed up
        @unlink($tempKernelFile);
    }

    /**
     * @param KernelInterface $parent
     * @param string          $namespace
     * @param string          $parentClass
     * @param string          $warmupDir
     *
     * @return KernelInterface
     */
    protected function getTempKernel(KernelInterface $parent, $namespace, $parentClass, $warmupDir)
    {
        $cacheDir = var_export($warmupDir, true);
        $rootDir = var_export(realpath($parent->getRootDir()), true);
        $logDir = var_export(realpath($parent->getLogDir()), true);
        // the temp kernel class name must have the same length than the real one
        // to avoid the many problems in serialized resources files
        $class = substr($parentClass, 0, -1).'_';
        // the temp kernel name must be changed too
        $name = var_export(substr($parent->getName(), 0, -1).'_', true);
        $code = <<<EOF
<?php

namespace $namespace
{
    class $class extends $parentClass
    {
        public function getCacheDir()
        {
            return $cacheDir;
        }

        public function getName()
        {
            return $name;
        }

        public function getRootDir()
        {
            return $rootDir;
        }

        public function getLogDir()
        {
            return $logDir;
        }

        protected function buildContainer()
        {
            \$container = parent::buildContainer();

            // filter container's resources, removing reference to temp kernel file
            \$resources = \$container->getResources();
            \$filteredResources = array();
            foreach (\$resources as \$resource) {
                if ((string) \$resource !== __FILE__) {
                    \$filteredResources[] = \$resource;
                }
            }

            \$container->setResources(\$filteredResources);

            return \$container;
        }
    }
}
EOF;
        $this->getContainer()->get('filesystem')->mkdir($warmupDir);
        file_put_contents($file = $warmupDir.'/kernel.tmp', $code);
        require_once $file;
        $class = "$namespace\\$class";

        return new $class($parent->getEnvironment(), $parent->isDebug());
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
