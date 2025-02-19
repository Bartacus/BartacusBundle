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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Normalizes and initializes the locale based on the current request.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $defaultLocale = 'en',
        private readonly ?RequestContextAwareInterface $router = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after the Router to have access to the _locale
            KernelEvents::REQUEST => [['onKernelRequest', 16]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $request->setDefaultLocale($this->defaultLocale);

        // the locale should only be resolved and set for the master request and not for its sub requests too
        if ($event->isMainRequest()) {
            $this->setLocale($request);
        }
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        $parentRequest = $this->requestStack->getParentRequest();

        // the locale should only be resolved and set for the master request and not for its sub requests too
        if (null !== $parentRequest && $event->isMainRequest()) {
            $this->setLocale($parentRequest);
        }
    }

    private function setLocale(Request $request): void
    {
        // get the locale from the current request
        $locale = $this->getLocaleFromRequest($request);
        if (!$locale) {
            return;
        }

        // remove encodings from the locale (usually set by TYPO3 SiteLanguages)
        $locale = \explode('.', $locale)[0];

        // normalize the determined locale (formatted as 'de_AT')
        $normalizedLocale = $this->normalizeLocale($locale);
        if (!$normalizedLocale) {
            return;
        }

        // use a normalized locale (e.g. 'de_AT') for the request
        $request->setLocale($normalizedLocale);

        if (null !== $this->router) {
            // use a url friendly locale for the router (e.g. 'de-at')
            $lowercaseLocale = \str_replace('_', '-', \mb_strtolower($normalizedLocale));
            $this->router->getContext()->setParameter('_locale', $lowercaseLocale);
        }
    }

    /**
     * Normalize locale to e.g. de_AT format.
     */
    private function normalizeLocale(string $locale): string
    {
        return \preg_replace_callback('/^([a-z]{2})[-_]([a-z]{2})$/i', static function (array $matches): string {
            return \sprintf('%s_%s', $matches[1], \mb_strtoupper($matches[2]));
        }, $locale);
    }

    /**
     * Get the locale from the current request.
     * Either fetched from an existing TYPO3 SiteLanguage or determined by the requested url.
     */
    private function getLocaleFromRequest(Request $request): ?string
    {
        // default TYPO3 Page behavior
        //  - check if a TYPO3 SiteLanguage already exists
        //  - this means this is a TYPO3 page request and we are something inside the TYPO3 middleware stack
        $siteLanguage = $request->attributes->get('language');
        if ($siteLanguage instanceof SiteLanguage) {
            return $siteLanguage->getLocale()->getName();
        }

        // default Symfony Route behavior
        //  - check if a locale could be extracted from a Symfony route (using {_locale} param during route generation)
        $locale = $request->attributes->get('_locale');
        if ($locale) {
            return $locale;
        }

        // fallback to the Site's default language
        $site = $request->attributes->get('site');

        if ($site instanceof Site) {
            return $site->getDefaultLanguage()->getLocale()->getName();
        }

        return null;
    }
}
