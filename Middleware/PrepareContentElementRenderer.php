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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class PrepareContentElementRenderer implements MiddlewareInterface
{
    private EventDispatcherInterface $dispatcher;
    private HttpKernelInterface $kernel;
    private RequestStack $requestStack;
    private HttpFoundationFactory $httpFoundationFactory;
    private PsrHttpFactory $psrHttpFactory;

    public function __construct(EventDispatcherInterface $dispatcher, HttpKernelInterface $kernel, RequestStack $requestStack)
    {
        $this->dispatcher = $dispatcher;
        $this->kernel = $kernel;
        $this->requestStack = $requestStack;

        $this->httpFoundationFactory = new HttpFoundationFactory();

        $psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withHeader('X-Php-Ob-Level', (string) \ob_get_level());
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $symfonyRequest = $this->httpFoundationFactory->createRequest($request);
        $symfonyRequest->attributes->set('_controller', 'typo3');

        $this->requestStack->push($symfonyRequest);

        $event = new RequestEvent($this->kernel, $symfonyRequest, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        SymfonyBootstrap::setRequestForTermination($symfonyRequest);

        $request = $this->psrHttpFactory->createRequest($symfonyRequest);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $handler->handle($request);

        $symfonyResponse = $this->httpFoundationFactory->createResponse($response);
        SymfonyBootstrap::setResponseForTermination($symfonyResponse);

        $event = new ResponseEvent($this->kernel, $symfonyRequest, HttpKernelInterface::MAIN_REQUEST, $symfonyResponse);
        $this->dispatcher->dispatch($event, KernelEvents::RESPONSE);

        $this->dispatcher->dispatch(
            new FinishRequestEvent($this->kernel, $symfonyRequest, HttpKernelInterface::MAIN_REQUEST),
            KernelEvents::FINISH_REQUEST
        );

        $this->requestStack->pop();

        $symfonyResponse = $event->getResponse();
        SymfonyBootstrap::setResponseForTermination($symfonyResponse);

        return $this->psrHttpFactory->createResponse($symfonyResponse);
    }
}
