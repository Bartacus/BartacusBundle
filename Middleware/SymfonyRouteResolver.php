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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SymfonyRouteResolver implements MiddlewareInterface
{
    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    /**
     * @var TypoScriptFrontendController
     */
    private $typoScriptFrontendController;

    /**
     * @var DiactorosFactory
     */
    private $psr7Factory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        TypoScriptFrontendController $typoScriptFrontendController,
        HttpKernelInterface $httpKernel,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->httpKernel = $httpKernel;

        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->psr7Factory = new DiactorosFactory();
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $symfonyRequest = $this->httpFoundationFactory->createRequest($this->prepareForSymfonyRequest($request));
        $symfonyResponse = null;

        // set the locale from TypoScript, effectively killing _locale from router :/
        [$locale] = \explode('.', $this->typoScriptFrontendController->config['config']['locale_all'] ?? 'en_GB.');
        $symfonyRequest->attributes->set('_locale', $locale);

        try {
            $symfonyResponse = $this->httpKernel->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (NotFoundHttpException $e) {
            // only catch when route matching failed
            if (!$e->getPrevious() instanceof  ResourceNotFoundException) {
                throw $e;
            }

            // no route found, but to initialize locale and translator correctly
            // dispatch request event again, but skip router.
            $symfonyRequest->attributes->set('_controller', 'typo3');

            // add back to request stack, because the finishRequest after exception popped.
            $this->requestStack->push($symfonyRequest);

            $event = new GetResponseEvent($this->httpKernel, $symfonyRequest, HttpKernelInterface::MASTER_REQUEST);
            $this->eventDispatcher->dispatch(KernelEvents::REQUEST, $event);
        }

        if ($symfonyResponse) {
            $response = $this->psr7Factory->createResponse($symfonyResponse);
        } else {
            $response = $handler->handle($request);
            $symfonyResponse = $this->httpFoundationFactory->createResponse($response);
        }

        SymfonyBootstrap::setRequestResponseForTermination($symfonyRequest, $symfonyResponse);

        return $response;
    }

    /**
     * Resolving the PageArguments (e.g. Typo3 page id) from the route.
     * This is normally done by typo3 in a later middleware, but we want to have this information in our symfony request.
     * Legacy URIs (?id=12345) are not supported for the time being (only routes).
     */
    protected function prepareForSymfonyRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $requestId = (string) ($request->getQueryParams()['id'] ?? '');

        /** @var Site $site */
        $site = $request->getAttribute('site', null);

        if ($requestId || !($site instanceof  SiteInterface)) {
            return $request;
        }

        $routeResult = $request->getAttribute('routing', null);
        if (!($routeResult->getLanguage() instanceof SiteLanguage)) {
            return $request;
        }

        try {
            /** @var PageArguments $pageArguments */
            $pageArguments = $site->getRouter()->matchRequest($request, $routeResult);
        } catch (RouteNotFoundException $e) {
            return $request;
        }

        $preparedRequest = $request->withAttribute('routing', $pageArguments);
        // merge the PageArguments with the request query parameters
        $queryParams = \array_replace_recursive(
            $preparedRequest->getQueryParams(),
            $pageArguments->getArguments()
        );

        return $preparedRequest->withQueryParams($queryParams);
    }
}
