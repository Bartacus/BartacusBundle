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

namespace Bartacus\Bundle\BartacusBundle\UpgradeWizard;

use Bartacus\Bundle\BartacusBundle\Scheduler\Proxy\TaskProxyFactory;
use ProxyManager\Configuration;
use ProxyManager\Inflector\ClassNameInflectorInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class TaskProxyUpdateWizard implements UpgradeWizardInterface, RepeatableInterface
{
    /**
     * Unique identifier for this wizard.
     *
     * @var string
     */
    public const IDENTIFIER = 'taskProxyUpdate';

    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    /**
     * @var Configuration
     */
    private $proxyConfiguration;

    /**
     * @var TaskProxyFactory
     */
    private $proxyFactory;

    /**
     * @var string[] array of the task class names to proxy
     */
    private $taskClasses;

    public function __construct(ConnectionPool $connectionPool, Configuration $proxyConfiguration, TaskProxyFactory $proxyFactory)
    {
        $this->connectionPool = $connectionPool;
        $this->proxyConfiguration = $proxyConfiguration;
        $this->proxyFactory = $proxyFactory;
    }

    public function setTaskClasses(string ...$taskClasses): void
    {
        $this->taskClasses = $taskClasses;
    }

    public function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    public function getTitle(): string
    {
        return 'Bartacus - Migrate proxied scheduler tasks';
    }

    public function getDescription(): string
    {
        return 'Migrates from the old proxied  class names to the new proxied ones.';
    }

    public function executeUpdate(): bool
    {
        $tasks = $this->fetchMigratableSchedulerTasks();
        foreach ($tasks as $task) {
            $taskUid = (int) $task['uid'];
            $serializedTaskObject = (string) $task['serialized_task_object'];

            // search them in the class mapping
            foreach ($this->taskClasses as $originalClassName) {
                // get the serialized string of the old class name
                $searchClassName = \sprintf(
                    '%s\\%s\\%s\\Generated%s',
                    $this->proxyConfiguration->getProxiesNamespace(),
                    ClassNameInflectorInterface::PROXY_MARKER,
                    $originalClassName,
                    \mb_substr($originalClassName, \mb_strrpos($originalClassName, '\\') + 1)
                );

                $search = \sprintf('O:%d:"%s', \mb_strlen($searchClassName) + 32, $searchClassName);

                // check if the original class name is used by this scheduler task and replace it
                if (false !== \mb_strpos($serializedTaskObject, $search)) {
                    // get the serialized string of the old class name
                    $proxyClassName = $this->proxyFactory->createProxy($originalClassName);
                    $replace = \sprintf('O:%d:"%s"', \mb_strlen($proxyClassName), $proxyClassName);

                    // replace the serialized class name string
                    $serializedTaskObject = \preg_replace(
                        '/^'.\str_replace('\\', '\\\\', $search).'[a-f0-9]{32}"/',
                        $replace,
                        $serializedTaskObject,
                        1
                    );

                    // update the database record
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
                    $queryBuilder
                        ->update('tx_scheduler_task')
                        ->set('serialized_task_object', $serializedTaskObject)
                        ->where($queryBuilder->expr()->eq('uid', $taskUid))
                    ;

                    $queryBuilder->execute();

                    break;
                }
            }
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        // check if any scheduler task contains proxied class names
        $records = $this->fetchMigratableSchedulerTasks();

        return \count($records) > 0;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * @return array[]
     */
    private function fetchMigratableSchedulerTasks(): array
    {
        $proxyNamespace = $this->proxyConfiguration->getProxiesNamespace();
        $search = 'O:%:"'.$proxyNamespace.'%';

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder
            ->select('*')
            ->from('tx_scheduler_task')
            ->where($queryBuilder->expr()->like('serialized_task_object', $queryBuilder->quote($search)))
        ;

        return $queryBuilder->execute()->fetchAll();
    }
}
