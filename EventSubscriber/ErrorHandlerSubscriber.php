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

namespace Bartacus\Bundle\BartacusBundle\EventSubscriber;

use Bartacus\Bundle\BartacusBundle\ConfigEvents;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3DebugExceptionHandler;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3ProductionExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ErrorHandlerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::ADDITIONAL_CONFIGURATION => [['registerErrorHandler', 2048]],
        ];
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function registerErrorHandler(Event $event): void
    {
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
    }
}
