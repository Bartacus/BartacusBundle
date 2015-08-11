<?php

/*
 * This file is part of the Bartacus project.
 *
 * Copyright (c) 2015 Patrik Karisch, pixelart GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Dispatch a content element to controller action
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class PluginDispatcher
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var HttpKernel
     */
    private $kernel;

    /**
     * @var RouterListener
     */
    private $routerListener;

    /**
     * @var ControllerResolverInterface
     */
    private $resolver;

    /**
     * @param RequestStack                $requestStack
     * @param HttpKernel                  $kernel
     * @param RouterListener              $routerListener
     * @param ControllerResolverInterface $resolver
     */
    public function __construct(
        RequestStack $requestStack,
        HttpKernel $kernel,
        RouterListener $routerListener,
        ControllerResolverInterface $resolver
    ) {
        $this->requestStack = $requestStack;
        $this->kernel = $kernel;
        $this->routerListener = $routerListener;
        $this->resolver = $resolver;
    }

    /**
     * @param string                $content       The content. Not used
     * @param array                 $configuration The TS configuration array
     * @param ContentObjectRenderer $cObj          The cObj calling this element
     *
     * @return string $content The processed content
     */
    public function handle($content, $configuration, $cObj)
    {
        $request = $this->requestStack->getCurrentRequest();

        $uri = '/'.$configuration['extensionName'].'/'.$configuration['pluginName'];
        $subRequest = $this->createSubRequest($uri, $request);
        $subRequest->attributes->set('data', $cObj->data);

        $subRequest->headers->set('X-Php-Ob-Level', ob_get_level());
        $this->requestStack->push($subRequest);

        $event = new GetResponseEvent(
            $this->kernel,
            $subRequest,
            HttpKernel::SUB_REQUEST
        );
        $this->routerListener->onKernelRequest($event);

        // load controller
        if (false === $controller = $this->resolver->getController(
                $subRequest
            )
        ) {
            throw new NotFoundHttpException(
                sprintf(
                    'Unable to find the controller for path "%s". The plugin is wrongly configured.',
                    $subRequest->getPathInfo()
                )
            );
        }

        // controller arguments
        $arguments = $this->resolver->getArguments($subRequest, $controller);

        // call controller
        $response = call_user_func_array($controller, $arguments);

        // view
        if (!$response instanceof Response) {
            $msg = sprintf(
                'The controller must return a response (%s given).',
                $this->varToString($response)
            );

            // the user may have forgotten to return something
            if (null === $response) {
                $msg .= ' Did you forget to add a return statement somewhere in your controller?';
            }
            throw new \LogicException($msg);
        }

        $this->routerListener->onKernelFinishRequest(
            new FinishRequestEvent(
                $this->kernel,
                $subRequest,
                HttpKernel::SUB_REQUEST
            )
        );
        $this->requestStack->pop();

        return $response->getContent();
    }

    /**
     * Creates a new sub request
     *
     * @param string  $uri
     * @param Request $request
     *
     * @return Request
     */
    protected function createSubRequest($uri, Request $request)
    {
        $method = $request->getMethod();
        $postMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

        $cookies = $request->cookies->all();
        $server = $request->server->all();
        $files = $request->files->all();

        $query = $request->query->all();
        $post = $request->request->all();

        $subRequest = Request::create(
            $uri,
            $method,
            in_array($method, $postMethods, true) ? $post : $query,
            $cookies,
            $files,
            $server
        );

        if (in_array($method, $postMethods, true)) {
            $subRequest->query->replace($query);
        } else {
            $subRequest->request->replace($post);
        }

        $session = $request->getSession();
        if ($session) {
            $subRequest->setSession($session);
        }

        $subRequest->setLocale($request->getLocale());
        $subRequest->setDefaultLocale($request->getDefaultLocale());

        return $subRequest;
    }

    /**
     * @param $var
     *
     * @return string
     */
    private function varToString($var)
    {
        if (is_object($var)) {
            return sprintf('Object(%s)', get_class($var));
        }

        if (is_array($var)) {
            $a = [];
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => %s', $k, $this->varToString($v));
            }

            return sprintf('Array(%s)', implode(', ', $a));
        }

        if (is_resource($var)) {
            return sprintf('Resource(%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'false';
        }

        if (true === $var) {
            return 'true';
        }

        return (string) $var;
    }
}
