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

use Bartacus\Bundle\BartacusBundle\Config\Event\AdditionalConfigurationEvent;
use Bartacus\Bundle\BartacusBundle\Config\Event\RequestExtbasePersistenceClassesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConfigLoader
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Fired by the project at the end of `config/system/additional.php`.
     * Loads content elements and triggers custom error handling in dev mode.
     */
    public function loadFromAdditionalConfiguration(): void
    {
        $event = new AdditionalConfigurationEvent();
        $this->eventDispatcher->dispatch($event, AdditionalConfigurationEvent::EVENT_NAME);
    }

    /**
     * Loads custom extbase configuration.
     * e.g. pixelart's extbase-domain-bundle.
     */
    public function loadFromRequestExtbasePersistenceClasses(): array
    {
        $event = new RequestExtbasePersistenceClassesEvent();
        $this->eventDispatcher->dispatch($event, RequestExtbasePersistenceClassesEvent::EVENT_NAME);

        return $event->getExtbasePersistenceClasses();
    }
}
