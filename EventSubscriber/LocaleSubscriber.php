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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContextAwareInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Normalizes and initializes the locale based on the current request.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestContextAwareInterface|null
     */
    private $router;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack                      $requestStack  A RequestStack instance
     * @param string                            $defaultLocale The default locale
     * @param RequestContextAwareInterface|null $router        The router
     */
    public function __construct(RequestStack $requestStack, string $defaultLocale = 'en', RequestContextAwareInterface $router = null)
    {
        $this->defaultLocale = $defaultLocale;
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $request->setDefaultLocale($this->defaultLocale);

        $this->setLocale($request);
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if (null !== $parentRequest = $this->requestStack->getParentRequest()) {
            $this->setLocale($parentRequest);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered after the Router to have access to the _locale
            KernelEvents::REQUEST => [['onKernelRequest', 16]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
        ];
    }

    private function setLocale(Request $request): void
    {
        if ($locale = $request->attributes->get('_locale')) {
            $normalizedLocale = $this->getLocaleFromTypo3($request) ?? $this->normalizeLocale($locale);
            $request->setLocale($normalizedLocale);

            if (null !== $this->router) {
                $this->router->getContext()->setParameter('_locale', $locale);
            }
        } elseif ($locale = $this->getLocaleFromTypo3($request)) {
            $request->setLocale($locale);

            $denormalizedLocale = $this->getDenormalizedLocaleFromTypo3($request);
            $request->attributes->set('_locale', $denormalizedLocale);

            if (null !== $this->router) {
                $this->router->getContext()->setParameter('_locale', $denormalizedLocale);
            }
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
     * Tries to get the exact locale from TYPO3 if it can be found in the request.
     */
    private function getLocaleFromTypo3(Request $request): ?string
    {
        $locale = null;

        if ((bool) $language = $request->attributes->get('language')) {
            /* @var SiteLanguage $language */
            [$locale] = \explode('.', $language->getLocale());
        } elseif ((bool) $site = $request->attributes->get('site')) {
            /* @var Site $site */
            $languageId = (int) $request->get('L');
            $siteLanguage = $site->getLanguageById($languageId);
            [$locale] = \explode('.', $siteLanguage->getLocale());
        }

        return $locale;
    }

    /**
     * Tries to get a denormalized locale for routing resolved from the base path.
     *
     * It is assumed here, that every site configuration has the locale as base path only.
     */
    private function getDenormalizedLocaleFromTypo3(Request $request): ?string
    {
        if ((bool) $language = $request->attributes->get('language')) {
            /* @var SiteLanguage $language */
            return \trim($language->getBase()->getPath(), '/');
        }

        if ((bool) $site = $request->attributes->get('site')) {
            /* @var Site $site */
            return \trim($site->getDefaultLanguage()->getBase()->getPath(), '/');
        }

        return null;
    }
}
