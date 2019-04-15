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
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class SymfonyRouteResolver implements MiddlewareInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var PsrHttpFactory
     */
    private $psrHttpFactory;

    public function __construct(HttpKernelInterface $kernel, Router $router)
    {
        $this->kernel = $kernel;
        $this->router = $router;

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

        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        $symfonyResponse = $this->kernel->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);
        SymfonyBootstrap::setRequestResponseForTermination($symfonyRequest, $symfonyResponse);

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }

    private function handleWithSymfony(ServerRequestInterface $request): bool
    {
        $fakeRequest = $this->createFakeRequest($request);

        try {
            $this->router->getContext()->fromRequest($fakeRequest);
            $this->router->matchRequest($fakeRequest);
        } catch (ResourceNotFoundException $e) {
            return false;
        } catch (MethodNotAllowedException $e) {
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

        if ($uri instanceof UriInterface) {
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
