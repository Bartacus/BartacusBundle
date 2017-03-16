<?php

/*
 * This file is part of the BartacusBundle.
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

declare(strict_types=1);

namespace Bartacus\Bundle\BartacusBundle\Http;

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Frontend\Http\EidRequestHandler;

/**
 * Entry point for the TYPO3 Frontend.
 */
class FrontendApplication implements ApplicationInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Number of subdirectories where the entry script is located, relative to PATH_site
     * Usually this is equal to PATH_site = 0.
     *
     * @var int
     */
    protected $entryPointLevel = 0;

    /**
     * All available request handlers that can deal with a Frontend Request.
     *
     * @var array
     */
    protected $availableRequestHandlers = [
        SymfonyFrontendRequestHandler::class,
        EidRequestHandler::class,
    ];

    /**
     * @var TerminableInterface
     */
    protected $kernel;

    /**
     * @var Request
     */
    protected static $request;

    /**
     * @var Response
     */
    protected static $response;

    /**
     * Constructor setting up legacy constant and register available Request Handlers.
     *
     * @param ClassLoader         $classLoader an instance of the class loader
     * @param TerminableInterface $kernel      The terminable Symfony http kernel
     */
    public function __construct(ClassLoader $classLoader, TerminableInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->defineLegacyConstants();

        $this->bootstrap = Bootstrap::getInstance()
            ->initializeClassLoader($classLoader)
            ->setRequestType(TYPO3_REQUESTTYPE_FE)
            ->baseSetup($this->entryPointLevel)
        ;

        // Redirect to install tool if base configuration is not found
        if (!$this->bootstrap->checkIfEssentialConfigurationExists()) {
            $this->bootstrap->redirectToInstallTool($this->entryPointLevel);
        }

        foreach ($this->availableRequestHandlers as $requestHandler) {
            $this->bootstrap->registerRequestHandlerImplementation($requestHandler);
        }

        $this->bootstrap->configure();
    }

    /**
     * Starting point.
     *
     * @param callable $execute
     */
    public function run(callable $execute = null): void
    {
        $this->bootstrap->handleRequest(ServerRequestFactory::fromGlobals());

        if ($execute !== null) {
            $execute();
        }

        $this->bootstrap->shutdown();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            Response::closeOutputBuffers(0, true);
        }

        $this->kernel->terminate(self::$request, self::$response);
    }

    /**
     * Saves the request and response for termination later, this is necessary
     * because of this horrible TYPO3 structure.
     *
     * @param Request  $request
     * @param Response $response
     */
    public static function setRequestResponseForTermination(Request $request, Response $response): void
    {
        self::$request = $request;
        self::$response = $response;
    }

    /**
     * Define constants and variables.
     */
    protected function defineLegacyConstants()
    {
        define('TYPO3_MODE', 'FE');
    }
}
