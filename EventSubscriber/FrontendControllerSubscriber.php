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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageRepository;

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

    public function onKernelRequest(RequestEvent $event): void
    {
        if (Kernel::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ('typo3' === $event->getRequest()->attributes->get('_controller')) {
            return;
        }

        $frontendController = $this->serviceBridge->getGlobal('TSFE');

        if ($frontendController) {
            if (!$frontendController->cObj instanceof ContentObjectRenderer) {
                $frontendController->newCObj();
            }

            if (!$frontendController->tmpl instanceof TemplateService) {
                $frontendController->tmpl = GeneralUtility::makeInstance(
                    TemplateService::class,
                    GeneralUtility::makeInstance(Context::class)
                );
            }

            $site = $event->getRequest()->attributes->get('site');
            if (empty($frontendController->tmpl->setup) && $site instanceof SiteInterface) {
                $frontendController->id = $site->getRootPageId();
                $frontendController->determineId();
                $frontendController->getConfigArray();
            }

            if (!$frontendController->sys_page instanceof PageRepository) {
                $frontendController->sys_page = GeneralUtility::makeInstance(
                    PageRepository::class,
                    GeneralUtility::makeInstance(Context::class)
                );

                $frontendController->settingLanguage();
                Locales::setSystemLocaleFromSiteLanguage($frontendController->getLanguage());
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }
}
