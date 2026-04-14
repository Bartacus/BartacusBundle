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

namespace Bartacus\Bundle\BartacusBundle\ErrorHandler;

use Bartacus\Bundle\BartacusBundle\Config\Event\AdditionalConfigurationEvent;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3\Typo3DebugExceptionHandler;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\Typo3\Typo3ProductionExceptionHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ErrorHandlerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AdditionalConfigurationEvent::EVENT_NAME => [['registerErrorHandler', 2048]],
        ];
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function registerErrorHandler(AdditionalConfigurationEvent $event): void
    {
        // Removes the TYPO3 error and exception handler to use Symfony instead, if in DEBUG mode and uses a custom
        // TYPO3 exception handler if not in DEBUG mode to fix the output buffer issue of the TwigBundle and
        // BartacusTwigBundle.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = $this->debug ? '' : Typo3DebugExceptionHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = $this->debug ? '' : Typo3ProductionExceptionHandler::class;
    }
}
