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

namespace Bartacus\Bundle\BartacusBundle\ContentElement\Loader;

use Bartacus\Bundle\BartacusBundle\Annotation\ContentElement;
use Bartacus\Bundle\BartacusBundle\ContentElement\Definition\RenderDefinition;
use Bartacus\Bundle\BartacusBundle\ContentElement\Definition\RenderDefinitionCollection;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class AnnotationContentElementLoader
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var array [name -> bundle metadata]
     */
    private $bundles;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var int
     */
    private $defaultRenderDefinitionIndex = 0;

    public function __construct(string $projectDir, array $bundles, Reader $annotationReader)
    {
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
        $this->annotationReader = $annotationReader;
    }

    public function load(): RenderDefinitionCollection
    {
        $collection = new RenderDefinitionCollection();
        $dirs = [];

        $directories = \scandir($this->projectDir.'/src');
        foreach ($directories as $directory) {
            if (\file_exists($dir = $this->projectDir.'/src/'.$directory.'/Action')) {
                $dirs[] = $dir;
            }

            if (\file_exists($dir = $this->projectDir.'/src/'.$directory.'/Controller')) {
                $dirs[] = $dir;
            }
        }

        foreach ($this->bundles as $name => $bundle) {
            if (\file_exists($dir = $bundle['path'].'/Action')) {
                $dirs[] = $dir;
            }

            if (\file_exists($dir = $bundle['path'].'/Controller')) {
                $dirs[] = $dir;
            }
        }

        if (\count($dirs)) {
            $finder = Finder::create()
                ->followLinks()
                ->files()
                ->name('*.php')
                ->in($dirs)
            ;

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $class = $this->findClass((string) $file);

                if ($class) {
                    $class = new \ReflectionClass($class);
                    if ($class->isAbstract()) {
                        continue;
                    }

                    $addResource = false;
                    foreach ($class->getMethods() as $method) {
                        $this->defaultRenderDefinitionIndex = 0;
                        foreach ($this->annotationReader->getMethodAnnotations($method) as $annot) {
                            if ($annot instanceof ContentElement) {
                                $addResource = true;
                                $this->addRenderDefinition($collection, $annot, $class, $method);
                            }
                        }
                    }

                    if ($addResource) {
                        $collection->addResource(new FileResource((string) $file));
                    }
                }
            }
        }

        return $collection;
    }

    private function addRenderDefinition(RenderDefinitionCollection $collection, ContentElement $annot, \ReflectionClass $class, \ReflectionMethod $method): void
    {
        $name = $annot->getName();
        if (null === $name) {
            $name = $this->getDefaultName($class, $method);
        }

        $cached = $annot->isCached();

        $renderDefinition = new RenderDefinition($name, $cached, $class->getName().'::'.$method->getName());
        $collection->add($renderDefinition);
    }

    private function getDefaultName(\ReflectionClass $class, \ReflectionMethod $method): string
    {
        $name = \mb_strtolower(\str_replace('\\', '_', $class->name).'_'.$method->name);
        if ($this->defaultRenderDefinitionIndex > 0) {
            $name .= '_'.$this->defaultRenderDefinitionIndex;
        }
        ++$this->defaultRenderDefinitionIndex;

        return \preg_replace([
            '/(action|bundle|controller)_/',
            '/action(_\d+)?$/',
            '/___invoke/',
            '/__/',
        ], [
            '_',
            '\\1',
            '',
            '_',
        ], $name);
    }

    /**
     * Returns the full class name for the first class in the file.
     *
     * @param string $file A PHP file path
     *
     * @return string|false Full class name if found, false otherwise
     */
    private function findClass(string $file)
    {
        $class = false;
        $namespace = false;
        $tokens = \token_get_all(\file_get_contents($file));

        if (1 === \count($tokens) && T_INLINE_HTML === $tokens[0][0]) {
            throw new \InvalidArgumentException(\sprintf('The file "%s" does not contain PHP code. Did you forgot to add the "<?php" start tag at the beginning of the file?',
                $file));
        }

        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];

            if (!isset($token[1])) {
                continue;
            }

            if (true === $class && T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && T_STRING === $token[0]) {
                $namespace = $token[1];
                while (isset($tokens[++$i][1]) && \in_array($tokens[$i][0], [T_NS_SEPARATOR, T_STRING], true)) {
                    $namespace .= $tokens[$i][1];
                }
                $token = $tokens[$i];
            }

            if (T_CLASS === $token[0]) {
                // Skip usage of ::class constant
                $isClassConstant = false;
                for ($j = $i - 1; $j > 0; --$j) {
                    if (!isset($tokens[$j][1])) {
                        break;
                    }

                    if (T_DOUBLE_COLON === $tokens[$j][0]) {
                        $isClassConstant = true;

                        break;
                    }

                    if (!\in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], true)) {
                        break;
                    }
                }

                if (!$isClassConstant) {
                    $class = true;
                }
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return false;
    }
}
