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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class PrepareContentElementRenderer implements MiddlewareInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var HttpKernelInterface
     */
    private $kernel;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HttpFoundationFactory
     */
    private $httpFoundationFactory;

    /**
     * @var PsrHttpFactory
     */
    private $psrHttpFactory;

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

        $event = new GetResponseEvent($this->kernel, $symfonyRequest, HttpKernel::MASTER_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::REQUEST, $event);

        $request = $this->psrHttpFactory->createRequest($symfonyRequest);
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $response = $handler->handle($request);

        $symfonyResponse = $this->httpFoundationFactory->createResponse($response);
        SymfonyBootstrap::setRequestResponseForTermination($symfonyRequest, $symfonyResponse);

        $event = new FilterResponseEvent($this->kernel, $symfonyRequest, HttpKernel::MASTER_REQUEST, $symfonyResponse);
        $this->dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->dispatcher->dispatch(KernelEvents::FINISH_REQUEST, new FinishRequestEvent($this->kernel, $symfonyRequest, HttpKernel::MASTER_REQUEST));
        $this->requestStack->pop();

        $symfonyResponse = $event->getResponse();
        SymfonyBootstrap::setRequestResponseForTermination($symfonyRequest, $symfonyResponse);

        $response = $this->psrHttpFactory->createResponse($symfonyResponse);

        return $response;
    }
}
