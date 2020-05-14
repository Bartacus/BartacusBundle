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

namespace Bartacus\Bundle\BartacusBundle\Config;

use Bartacus\Bundle\BartacusBundle\ConfigEvents;
use Bartacus\Bundle\BartacusBundle\Event\ExtensionLocalConfLoadEvent;
use Bartacus\Bundle\BartacusBundle\Event\ExtensionTablesLoadEvent;
use Bartacus\Bundle\BartacusBundle\Event\RequestExtbasePersistenceClassesEvent;
use Bartacus\Bundle\BartacusBundle\Event\RequestMiddlewaresEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Delegating central config loader called on various places within TYPO3
 * to load and configure specific parts of the system.
 */
class ConfigLoader
{
    public const DEFAULT_EXTENSION = 'app';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = LegacyEventDispatcherProxy::decorate($eventDispatcher);
    }

    public function loadFromAdditionalConfiguration(): void
    {
        $this->eventDispatcher->dispatch(new Event(), ConfigEvents::ADDITIONAL_CONFIGURATION);
    }

    public function loadFromRequestMiddlewares(): array
    {
        $event = new RequestMiddlewaresEvent();
        $this->eventDispatcher->dispatch($event, ConfigEvents::REQUEST_MIDDLEWARES);

        return $event->getRequestMiddlewares();
    }

    public function loadFromExtensionTables(string $extension = self::DEFAULT_EXTENSION): void
    {
        $this->eventDispatcher->dispatch(new ExtensionTablesLoadEvent($extension), ConfigEvents::EXTENSION_TABLES);
    }

    public function loadFromExtensionLocalConf(string $extension = self::DEFAULT_EXTENSION): void
    {
        $this->eventDispatcher->dispatch(new ExtensionLocalConfLoadEvent($extension), ConfigEvents::EXTENSION_LOCAL_CONF);
    }

    public function loadFromRequestExtbasePersistenceClasses(): array
    {
        $event = new RequestExtbasePersistenceClassesEvent();
        $this->eventDispatcher->dispatch($event, ConfigEvents::REQUEST_EXTBASE_PERSISTENCE_CLASSES);

        return $event->getExtbasePersistenceClasses();
    }
}
