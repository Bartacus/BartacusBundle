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
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3DebugExceptionHandler;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3ProductionExceptionHandler;
use Bartacus\Bundle\BartacusBundle\Event\RequestMiddlewaresEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Delegating central config loader called on various places within TYPO3
 * to load and configure specific parts of the system.
 */
class ConfigLoader
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(EventDispatcherInterface $eventDispatcher, bool $debug)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $debug;
    }

    public function loadFromAdditionalConfiguration(): void
    {
        $this->eventDispatcher->dispatch(ConfigEvents::ADDITIONAL_CONFIGURATION);

        if ($this->debug) {
            // remove TYPO3 error and exception handler to use Symfony instead, if in DEBUG mode
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = '';
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = '';
        } else {
            // use custom TYPO3 exception handler if not in DEBUG mode to fix the Output Buffer issue of
            // the TwigBundle and BartacusTwigBundle
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = Typo3DebugExceptionHandler::class;
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = Typo3ProductionExceptionHandler::class;
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'] = '';
    }

    public function loadFromRequestMiddlewares(): array
    {
        $event = new RequestMiddlewaresEvent();
        $this->eventDispatcher->dispatch(ConfigEvents::REQUEST_MIDDLEWARES, $event);

        return $event->getRequestMiddlewares();
    }
}
