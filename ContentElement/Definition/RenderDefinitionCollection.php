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

namespace Bartacus\Bundle\BartacusBundle\ContentElement\Definition;

use Symfony\Component\Config\Resource\ResourceInterface;

class RenderDefinitionCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var RenderDefinition[]
     */
    private array $renderDefinitions = [];

    /**
     * @var ResourceInterface[]
     */
    private array $resources = [];

    /**
     * @return \ArrayIterator|RenderDefinition[]
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->renderDefinitions);
    }

    /**
     * Gets the number of Routes in this collection.
     */
    public function count(): int
    {
        return \count($this->renderDefinitions);
    }

    public function add(RenderDefinition $renderDefinition): void
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

    public function getResources(): array
    {
        return \array_unique($this->resources);
    }

    public function addResource(ResourceInterface $resource): void
    {
        $this->resources[] = $resource;
    }

    public function addCollection(self $collection): void
    {
        $this->renderDefinitions = \array_merge($this->renderDefinitions, $collection->all());
        $this->resources = \array_merge($this->resources, $collection->getResources());
    }
}
