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

namespace Bartacus\Bundle\BartacusBundle\ContentElement\Definition;

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class RenderDefinitionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var RenderDefinition[]
     */
    private $renderDefinitions = [];

    /**
     * @var array
     */
    private $resources = [];

    /**
     * Gets the current RouteCollection as an Iterator that includes all routes.
     *
     * It implements \IteratorAggregate.
     *
     * @see all()
     *
     * @return \ArrayIterator|RenderDefinition[] An \ArrayIterator object for iterating over routes
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->renderDefinitions);
    }

    /**
     * Gets the number of Routes in this collection.
     *
     * @return int The number of routes
     */
    public function count(): int
    {
        return \count($this->renderDefinitions);
    }

    /**
     * @param RenderDefinition $renderDefinition
     */
    public function add(RenderDefinition $renderDefinition)
    {
        $this->renderDefinitions[] = $renderDefinition;
    }

    /**
     * @return RenderDefinition[]
     */
    public function all(): array
    {
        return $this->renderDefinitions;
    }

    /**
     * Returns an array of resources loaded to build this collection.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources(): array
    {
        return \array_unique($this->resources);
    }

    /**
     * Adds a resource for this collection.
     *
     * @param ResourceInterface $resource A resource instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * Adds a render definition collection at the end of the current set by appending all
     * render definitions of the added collection.
     *
     * @param RenderDefinitionCollection $collection
     */
    public function addCollection(RenderDefinitionCollection $collection)
    {
        $this->renderDefinitions = \array_merge($this->renderDefinitions, $collection->all());
        $this->resources = \array_merge($this->resources, $collection->getResources());
    }
}
