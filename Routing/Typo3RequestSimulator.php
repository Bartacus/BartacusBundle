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

namespace Bartacus\Bundle\BartacusBundle\Routing;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Middleware\PrepareTypoScriptFrontendRendering;

class Typo3RequestSimulator
{
    public function __construct(
        private readonly Context $context,
        private readonly DummyRequestHandler $dummyRequestHandler,
        private readonly PrepareTypoScriptFrontendRendering $typoScriptFrontendRendering,
    ) {
    }

    /**
     * @throws \RuntimeException
     */
    public function simulateWebRequest(?ServerRequestInterface $request, ?Site $fallbackSite = null): ?ServerRequestInterface
    {
        $request = $this->getWebRequest($request, $fallbackSite);
        if (!$request) {
            return null;
        }

        // abort if we dont know the site and could not fake it
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $request;
        }

        // cache the origin attributes for rollbacks
        $previousRouting = $request->getAttribute('routing');

        // simulate a TYPO3 request to the site's root page in order to generate the template config, ...
        $fakePageArguments = new PageArguments($site->getRootPageId(), '0', []);
        $request = $request->withAttribute('routing', $fakePageArguments);

        // set language aspect
        $language = $request->getAttribute('language', $site->getDefaultLanguage());
        $language = $language instanceof SiteLanguage ? $language : $site->getDefaultLanguage();
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($language);
        $this->context->setAspect('language', $languageAspect);

        // avoid frontend caching and build the full config
        $cacheInstruction = new CacheInstruction();
        $cacheInstruction->disableCache('Symfony Routes need a fresh and full Setup config.');
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);

        // trigger TYPO3 page rendering to build TypoScript config, ...
        $this->typoScriptFrontendRendering->process($request, $this->dummyRequestHandler);
        $request = $this->dummyRequestHandler->getRequestProcessedByTypoScriptRendering();

        // unset the routing changes
        $request = $request->withAttribute('routing', $previousRouting);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $request;
    }

    private function getWebRequest(?ServerRequestInterface $request, ?Site $fallbackSite = null): ?ServerRequestInterface
    {
        if ($request) {
            return $request;
        }

        if (!$fallbackSite) {
            // use the 1st site which has a valid hostname as fallback site
            $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();

            foreach ($sites as $possibleSite) {
                if ($possibleSite->getBase()->getHost()) {
                    $fallbackSite = $possibleSite;
                    break;
                }
            }

            if (!$fallbackSite instanceof Site) {
                return null;
            }
        }

        // create a fake backend request - same as if someone would click on the planer task execution button
        $request = new ServerRequest(
            $fallbackSite->getBase()->getScheme().'//'.$fallbackSite->getBase()->getHost().'/typo3/module/scheduler/manage',
            'POST'
        );

        return $request->withAttribute('site', $fallbackSite);
    }
}
