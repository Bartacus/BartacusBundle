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

namespace Bartacus\Bundle\BartacusBundle\Scheduler;

use Bartacus\Bundle\BartacusBundle\Scheduler\Proxy\TaskProxyFactory;
use ProxyManager\Configuration;

final class TaskGenerator
{
    /**
     * @var Configuration
     */
    private $proxyConfiguration;

    /**
     * @var TaskProxyFactory
     */
    private $proxyFactory;

    /**
     * @var string[] Array of task classes to proxy
     */
    private $tasks;

    public function __construct(Configuration $proxyConfiguration, TaskProxyFactory $proxyFactory, array $tasks)
    {
        $this->proxyConfiguration = $proxyConfiguration;
        $this->proxyFactory = $proxyFactory;
        $this->tasks = $tasks;
    }

    public function registerAutoloader(): void
    {
        \spl_autoload_register($this->proxyConfiguration->getProxyAutoloader());
    }

    /**
     * Generate all task proxy classes.
     *
     * @return array An mapping of the task class name to the proxy class name
     */
    public function generateAll(): array
    {
        $mapping = [];

        foreach ($this->tasks as $task) {
            $mapping[$task] = $this->proxyFactory->createProxy($task);
        }

        return $mapping;
    }
}
