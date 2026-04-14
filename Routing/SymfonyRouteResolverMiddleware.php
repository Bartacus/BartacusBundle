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

class SymfonyRouteResolverMiddleware implements MiddlewareInterface
{
    private PsrHttpFactory $psrHttpFactory;
    private HttpFoundationFactory $httpFoundationFactory;

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly Router $router,
        private readonly Typo3RequestSimulator $typo3RequestSimulator,
    ) {
        $psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $this->httpFoundationFactory = new HttpFoundationFactory();
    }

    /**
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isSymfonyRoute($request)) {
            return $handler->handle($request);
        }

        $request = $this->typo3RequestSimulator->simulateWebRequest($request);

        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        SymfonyBootstrap::setRequestForTermination($symfonyRequest);

        $symfonyResponse = $this->kernel->handle($symfonyRequest, HttpKernelInterface::MAIN_REQUEST, false);
        SymfonyBootstrap::setResponseForTermination($symfonyResponse);

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }

    private function isSymfonyRoute(ServerRequestInterface $request): bool
    {
        $fakeRequest = $this->createFakeSymfonyRequest($request);

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
    private function createFakeSymfonyRequest(ServerRequestInterface $psrRequest): Request
    {
        $uri = $psrRequest->getUri();

        $server = [
            'SERVER_NAME' => $uri->getHost(),
            'SERVER_PORT' => $uri->getPort(),
            'REQUEST_URI' => $uri->getPath(),
            'QUERY_STRING' => $uri->getQuery(),
            'REQUEST_METHOD' => $psrRequest->getMethod(),
        ];

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
