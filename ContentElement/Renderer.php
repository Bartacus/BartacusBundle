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

namespace Bartacus\Bundle\BartacusBundle\ContentElement;

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\ControllerDoesNotReturnResponseException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\ErrorController;

class Renderer
{
    /**
     * Inject by the user function call from TYPO3 :/.
     */
    public ?ContentObjectRenderer $cObj = null;
    private RequestStack $requestStack;
    private HttpKernel $kernel;
    private ControllerResolverInterface $resolver;
    private ArgumentResolverInterface $argumentResolver;
    private EventDispatcherInterface $dispatcher;
    private ErrorController $errorController;
    private PsrHttpFactory $psrHttpFactory;

    public function __construct(
        RequestStack $requestStack,
        HttpKernel $kernel,
        ControllerResolverInterface $resolver,
        ArgumentResolverInterface $argumentResolver,
        EventDispatcherInterface $eventDispatcher,
        ErrorController $errorController
    ) {
        $this->requestStack = $requestStack;
        $this->kernel = $kernel;
        $this->resolver = $resolver;
        $this->argumentResolver = $argumentResolver;
        $this->dispatcher = $eventDispatcher;
        $this->errorController = $errorController;

        $psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    /**
     * This setter is called when the plugin is called from UserContentObject (USER)
     * via ContentObjectRenderer->callUserFunction().
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * @throws ImmediateResponseException
     * @throws PageNotFoundException
     * @noinspection PhpUnusedParameterInspection
     */
    public function handle(string $content, array $configuration): string
    {
        if (!$this->requestStack->getMainRequest()) {
            throw new BadRequestException('Main request not found.');
        }

        $request = $this->requestStack->getMainRequest()->duplicate();

        $request->attributes->set('data', $this->cObj->data);
        $request->attributes->set('_controller', $configuration['controller']);
        $request->headers->set('X-Php-Ob-Level', (string) \ob_get_level());

        $this->requestStack->push($request);
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $request);
        }

        // load controller
        $controller = $this->resolver->getController($request);
        if (false === $controller) {
            throw new NotFoundHttpException(\sprintf(
                'Unable to find the controller "%s". The content element is wrongly configured.',
                $request->attributes->get('_controller', $configuration['controller'])
            ));
        }

        $event = new ControllerEvent($this->kernel, $controller, $request, HttpKernelInterface::SUB_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER);
        $controller = $event->getController();

        // controller arguments
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        $event = new ControllerArgumentsEvent($this->kernel, $controller, $arguments, $request, HttpKernelInterface::SUB_REQUEST);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER_ARGUMENTS);
        $controller = $event->getController();
        $arguments = $event->getArguments();

        try {
            // call controller
            $response = $controller(...$arguments);
        } catch (NotFoundHttpException $e) {
            $psrResponse = $this->errorController->pageNotFoundAction(
                $this->psrHttpFactory->createRequest($request),
                $e->getMessage()
            );

            throw new ImmediateResponseException($psrResponse);
        }

        // view
        if (!$response instanceof Response) {
            $event = new ViewEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
            $this->dispatcher->dispatch($event, KernelEvents::VIEW);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            } else {
                $msg = \sprintf(
                    'The controller must return a "Symfony\Component\HttpFoundation\Response" object but it returned "%s".',
                    \get_debug_type($response)
                );

                // the user may have forgotten to return something
                if (null === $response) {
                    $msg .= ' Did you forget to add a return statement somewhere in your controller?';
                }

                throw new ControllerDoesNotReturnResponseException($msg, $controller, __FILE__, __LINE__ - 23);
            }
        }

        return $this->filterResponse($response, $request);
    }

    private function filterResponse(Response $response, Request $request): string
    {
        $event = new ResponseEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
        $this->dispatcher->dispatch($event, KernelEvents::RESPONSE);
        $this->dispatcher->dispatch(
            new FinishRequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST),
            KernelEvents::FINISH_REQUEST
        );

        $this->requestStack->pop();

        $request->attributes->remove('data');
        $request->attributes->set('_controller', 'typo3');

        $response = $event->getResponse();

        if ($response instanceof RedirectResponse) {
            $response->send();
            $this->kernel->terminate($request, $response);

            exit();
        }

        if (\count($response->headers) || Response::HTTP_OK !== $response->getStatusCode()) {
            $response->sendHeaders();
        }

        return $response->getContent();
    }
}
