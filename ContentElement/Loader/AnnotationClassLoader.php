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

use Bartacus\Bundle\BartacusBundle\Annotation\ContentElement;
use Bartacus\Bundle\BartacusBundle\ContentElement\RenderDefinition;
use Bartacus\Bundle\BartacusBundle\ContentElement\RenderDefinitionCollection;
use Doctrine\Common\Annotations\Reader;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @DI\Service("bartacus.content_element.class_loader", public=false)
 */
class AnnotationClassLoader implements LoaderInterface
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var int
     */
    protected $defaultRenderDefinitionIndex = 0;

    /**
     * @DI\InjectParams(params={
     *      "reader" = @DI\Inject("annotation_reader")
     * })
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Loads from annotations from a class.
     *
     * @param string      $class A class name
     * @param string|null $type  The resource type
     *
     * @throws \InvalidArgumentException When render defintion can't be parsed
     *
     * @return RenderDefinitionCollection
     */
    public function load($class, $type = null): RenderDefinitionCollection
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $class = new \ReflectionClass($class);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.',
                $class->getName()));
        }

        $collection = new RenderDefinitionCollection();
        $collection->addResource(new FileResource($class->getFileName()));

        foreach ($class->getMethods() as $method) {
            $this->defaultRenderDefinitionIndex = 0;
            foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                if ($annot instanceof ContentElement) {
                    $this->addRenderDefinition($collection, $annot, $class, $method);
                }
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($class, $type = null): bool
    {
        return is_string($class) && preg_match('/^(?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $class)
        && (!$type || 'annotation' === $type);
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
    }

    protected function addRenderDefinition(RenderDefinitionCollection $collection, ContentElement $annot, \ReflectionClass $class, \ReflectionMethod $method): void
    {
        $name = $annot->getName();
        if (null === $name) {
            $name = $this->getDefaultName($class, $method);
        }
        $cached = $annot->isCached();

        $renderDefinition = new RenderDefinition($name, $cached, $class->getName().'::'.$method->getName());
        $collection->add($renderDefinition);
    }

    protected function getDefaultName(\ReflectionClass $class, \ReflectionMethod $method): string
    {
        $name = strtolower(str_replace('\\', '_', $class->name).'_'.$method->name);
        if ($this->defaultRenderDefinitionIndex > 0) {
            $name .= '_'.$this->defaultRenderDefinitionIndex;
        }
        ++$this->defaultRenderDefinitionIndex;

        return preg_replace([
            '/(bundle|controller)_/',
            '/action(_\d+)?$/',
            '/__/',
        ], [
            '_',
            '\\1',
            '_',
        ], $name);
    }
}
