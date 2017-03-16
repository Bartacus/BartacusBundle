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

namespace Bartacus\Bundle\BartacusBundle\ContentElement\Loader;

use Bartacus\Bundle\BartacusBundle\ContentElement\RenderDefinitionCollection;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @DI\Service("bartacus.content_element.loader")
 */
class AnnotationBundleLoader extends FileLoader
{
    /**
     * @var AnnotationClassLoader
     */
    private $loader;

    /**
     * @DI\InjectParams(params={
     *      "locator" = @DI\Inject("file_locator"),
     *      "loader" = @DI\Inject("bartacus.content_element.class_loader")
     * })
     */
    public function __construct(FileLocatorInterface $locator, AnnotationClassLoader $loader)
    {
        if (!function_exists('token_get_all')) {
            throw new \RuntimeException('The Tokenizer extension is required for the routing annotation loaders.');
        }

        parent::__construct($locator);

        $this->loader = $loader;
    }

    /**
     * Loads from annotations from bundles.
     *
     * @param array       $bundles A array of bundles
     * @param string|null $type    The resource type
     *
     * @throws \Exception
     *
     * @return RenderDefinitionCollection
     */
    public function load($bundles, $type = null): RenderDefinitionCollection
    {
        $collection = new RenderDefinitionCollection();
        foreach ($bundles as $bundle) {
            try {
                $dir = $this->locator->locate('@'.$bundle.'/Controller/');
            } catch (\Exception $e) {
                // No controllers in that bundle..
                continue;
            }

            $collection->addResource(new DirectoryResource($dir, '/\.php$/'));
            $files = iterator_to_array(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                )
            );

            usort($files, function (\SplFileInfo $a, \SplFileInfo $b) {
                return (string) $a > (string) $b ? 1 : -1;
            });

            /** @var \SplFileInfo $file */
            foreach ($files as $file) {
                if (!$file->isFile() || '.php' !== substr($file->getFilename(), -4)) {
                    continue;
                }

                $class = $this->findClass((string) $file);
                if ($class) {
                    $refl = new \ReflectionClass($class);
                    if ($refl->isAbstract()) {
                        continue;
                    }

                    $collection->addCollection($this->loader->load($class, $type));
                }

                if (PHP_VERSION_ID >= 70000) {
                    // PHP 7 memory manager will not release after token_get_all(), see https://bugs.php.net/70098
                    gc_mem_caches();
                }
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($bundles, $type = null): bool
    {
        if (!is_array($bundles)) {
            return false;
        }

        return 'annotation' === $type;
    }

    /**
     * Returns the full class name for the first class in the file.
     *
     * @param string $file A PHP file path
     *
     * @return string|false Full class name if found, false otherwise
     */
    protected function findClass(string $file)
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($file));
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
                while (isset($tokens[++$i][1]) && in_array($tokens[$i][0], [T_NS_SEPARATOR, T_STRING], true)) {
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
                    } elseif (!in_array($tokens[$j][0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], true)) {
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
