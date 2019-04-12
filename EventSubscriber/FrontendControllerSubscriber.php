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

use Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Initializes the several stuff on the TSFE, when not done yet.
 */
class FrontendControllerSubscriber implements EventSubscriberInterface
{
    /**
     * @var ServiceBridge
     */
    private $serviceBridge;

    public function __construct(ServiceBridge $serviceBridge)
    {
        $this->serviceBridge = $serviceBridge;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (Kernel::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ('typo3' === $event->getRequest()->attributes->get('_controller')) {
            return;
        }

        $frontendController = $this->serviceBridge->getGlobal('TSFE');
        if ($frontendController && !$frontendController->cObj instanceof ContentObjectRenderer) {
            $frontendController->newCObj();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }
}
