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
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\RootlineUtility;

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
        // extract the TYPO3 Site and SiteLanguage models resolved by the SiteResolver middleware
        $site = $request->attributes->get('site');
        $siteLanguage = $request->attributes->get('language');

        // default behavior
        // TYPO3 resolved a SiteLanguage model based on the lozalised request path.
        if ($siteLanguage instanceof SiteLanguage) {
            // keep only the real locale like 'en_GB' and remove the encoding suffix (like '.UTF-8')
            [$locale] = \explode('.', $siteLanguage->getLocale());

            return $locale;
        }

        // TYPO3 resolved a real Site but without a SiteLanguage
        // This case occur if the request path matches a TYPO3 page, but the request path has no localized segment.
        // Common cases are requesting the web root ('/'). Typically this case is useless as this route should be
        // redirected to force a localized segment as request path prefix.
        if ($site instanceof Site) {
            // check if the language information is specified in the query as of TYPO v8
            // the request uses the TYPO3 v9 url structure and the TYPO3 v8 query parameter(s)
            if ($request->query->has('L')) {
                try {
                    // use the SiteLanguage which matches the requested sys language uid
                    $siteLanguage = $site->getLanguageById((int) $request->query->get('L'));
                } catch (\InvalidArgumentException $exception) {
                    // the exception will be thrown if there is no SiteLanguage matching the requested sys language uid,
                    // Instead of throwing the exeption we will use a fallback to the Site's default language.
                }
            }

            // fallback to the Site's default language if either the language information is not set in the query
            // parameters or if the requested sys langauge uid does not match any SiteLanguage.
            if (!$siteLanguage instanceof SiteLanguage) {
                $siteLanguage = $site->getDefaultLanguage();
            }

            // keep only the real locale like 'en_GB' and remove the encoding suffix (like '.UTF-8')
            [$locale] = \explode('.', $siteLanguage->getLocale());

            return $locale;
        }

        // TYPO3 resolved neither a Site nor a SiteLanguage model
        // This case occur if the request uses the old TYPO3 v8 url structure like SOLR does, which means the
        // SiteResolver will already create and return a PseudoSite with incomplete SiteLanguage models.
        // The request looks like 'index.php?id=123&L=5'

        // if the request does not contain any language and page information
        // there is nothing we can do to extract a locale
        if (!$request->query->has('id') && !$request->query->has('L')) {
            return null;
        }

        // get all Site models configured for this project
        $siteFinder = new SiteFinder();
        $availableSites = $siteFinder->getAllSites();

        // if the project has only one Site configured we can convert the PseudoSite to the real Site and use
        // the SiteLanguage which fits the requested sys language uid
        if (1 === count($availableSites)) {
            /** @var Site $site */
            $site = array_values($availableSites)[0];

            try {
                // use the SiteLanguage which matches the requested sys language uid
                $siteLanguage = $site->getLanguageById((int) $request->query->get('L'));
            } catch (\InvalidArgumentException $exception) {
                // the exception will be thrown if there is no SiteLanguage matching the requested sys language uid,
                // Instead of throwing the exeption we will use a fallback to the Site's default language.
                $siteLanguage = $site->getDefaultLanguage();
            }

            // keep only the real locale like 'en_GB' and remove the encoding suffix (like '.UTF-8')
            [$locale] = \explode('.', $siteLanguage->getLocale());

            return $locale;
        }

        // get the root line of the requested page as we need its root page id to get the Site which matches the
        // requested page
        $rootLinePages = (new RootlineUtility((int) $request->query->get('id')))->get();
        $rootPage = (array) end($rootLinePages);

        // verify and extract the uid of the resolved root page
        if (array_key_exists('uid', $rootPage) && array_key_exists('is_siteroot', $rootPage) && (bool) $rootPage['is_siteroot']) {
            $rootPageId = (int) $rootPage['uid'];
        } else {
            // root page not found - there is something wrong in the TYPO3 backend page tree
            return null;
        }

        // try to find a configured Site which matches the resolved root page
        try {
            $site = $siteFinder->getSiteByRootPageId($rootPageId);
        } catch (SiteNotFoundException $exception) {
            // the exception will be thrown if there is no Site configured which matches the resolved root page
            // there is no chance to find the Site which matches the requested  page to lookup its SiteLanguages
            return null;
        }

        try {
            // use the SiteLanguage which matches the requested sys language uid
            $siteLanguage = $site->getLanguageById((int) $request->query->get('L'));
        } catch (\InvalidArgumentException $exception) {
            // the exception will be thrown if there is no SiteLanguage matching the requested sys language uid,
            // Instead of throwing the exeption we will use a fallback to the Site's default language.
            $siteLanguage = $site->getDefaultLanguage();
        }

        // keep only the real locale like 'en_GB' and remove the encoding suffix (like '.UTF-8')
        [$locale] = \explode('.', $siteLanguage->getLocale());

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
