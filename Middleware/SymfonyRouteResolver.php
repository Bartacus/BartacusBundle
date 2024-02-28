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

namespace Bartacus\Bundle\BartacusBundle\Middleware;

use Bartacus\Bundle\BartacusBundle\Bootstrap\SymfonyBootstrap;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Middleware\TypoScriptFrontendInitialization;

class SymfonyRouteResolver implements MiddlewareInterface
{
    private HttpKernelInterface $kernel;
    private Router $router;
    private HttpFoundationFactory $httpFoundationFactory;
    private PsrHttpFactory $psrHttpFactory;
    private Context $context;
    private RequestHandlerInterface $dummyRequestHandler;
    private TypoScriptFrontendInitialization $frontendInitialization;

    public function __construct(
        HttpKernelInterface $kernel,
        Router $router,
        Context $context,
        RequestHandlerInterface $dummyRequestHandler,
        TypoScriptFrontendInitialization $frontendInitialization
    ) {
        $this->kernel = $kernel;
        $this->router = $router;
        $this->context = $context;
        $this->dummyRequestHandler = $dummyRequestHandler;
        $this->frontendInitialization = $frontendInitialization;

        $this->httpFoundationFactory = new HttpFoundationFactory();

        $psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    /**
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->handleWithSymfony($request)) {
            return $handler->handle($request);
        }

        $this->initializeTemporaryTSFE($request);

        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        SymfonyBootstrap::setRequestForTermination($symfonyRequest);

        $symfonyResponse = $this->kernel->handle($symfonyRequest, HttpKernelInterface::MAIN_REQUEST, false);
        SymfonyBootstrap::setResponseForTermination($symfonyResponse);

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }

    private function initializeTemporaryTSFE(ServerRequestInterface $request): void
    {
        if ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            return;
        }

        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            return;
        }

        $previousRouting = $request->getAttribute('routing', null);
        if (!$previousRouting instanceof SiteRouteResult) {
            return;
        }

        $fakeRouting = new SiteRouteResult(
            $previousRouting->getUri(),
            $previousRouting->getSite(),
                $previousRouting->getLanguage() ?? $site->getDefaultLanguage()
        );

        try {
            $pageArguments = $site->getRouter()->matchRequest($request, $fakeRouting);
            $request = $request->withAttribute('routing', $pageArguments);
        } catch (RouteNotFoundException) {
            // route cannot be found if it's a symfony route
        }

        // set language aspect
        $language = $request->getAttribute('language', $site->getDefaultLanguage());
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($language);
        $this->context->setAspect('language', $languageAspect);

        $this->frontendInitialization->process($request, $this->dummyRequestHandler);

        // unset the changes
        $request = $request->withAttribute('routing', $previousRouting);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function handleWithSymfony(ServerRequestInterface $request): bool
    {
        $fakeRequest = $this->createFakeRequest($request);

        try {
            $this->router->getContext()->fromRequest($fakeRequest);
            $this->router->matchRequest($fakeRequest);
        } catch (ResourceNotFoundException|MethodNotAllowedException) {
            return false;
        }

        return true;
    }

    /**
     * Create a fake Symfony request without files and without body to match.
     */
    private function createFakeRequest(ServerRequestInterface $psrRequest): Request
    {
        $server = [];
        $uri = $psrRequest->getUri();

        if ($uri) {
            $server['SERVER_NAME'] = $uri->getHost();
            $server['SERVER_PORT'] = $uri->getPort();
            $server['REQUEST_URI'] = $uri->getPath();
            $server['QUERY_STRING'] = $uri->getQuery();
        }

        $server['REQUEST_METHOD'] = $psrRequest->getMethod();

        $server = \array_replace($server, $psrRequest->getServerParams());

        $parsedBody = $psrRequest->getParsedBody();
        $parsedBody = \is_array($parsedBody) ? $parsedBody : [];

        $request = new Request(
            $psrRequest->getQueryParams(),
            $parsedBody,
            $psrRequest->getAttributes(),
            $psrRequest->getCookieParams(),
            [],
            $server,
            ''
        );

        $request->headers->replace($psrRequest->getHeaders());

        return $request;
    }
}
