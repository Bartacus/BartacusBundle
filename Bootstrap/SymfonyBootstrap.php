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

namespace Bartacus\Bundle\BartacusBundle\Bootstrap;

use App\Kernel as AppKernel;
use Bartacus\Bundle\BartacusBundle\ErrorHandler\SymfonyErrorHandler;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use TYPO3\CMS\Core\Http\ServerRequest;

final class SymfonyBootstrap
{
    private static ?Kernel $kernel = null;
    private static ?Request $request = null;
    private static ?Response $response = null;

    public static function getKernel(): ?Kernel
    {
        return self::$kernel;
    }

    public static function initKernel(): void
    {
        /** @var Kernel $fakeKernel */
        $fakeKernel = new AppKernel('prod', false);
        $projectDir = $fakeKernel->getProjectDir();

        require $projectDir.'/config/bootstrap.php';

        if ($_SERVER['APP_DEBUG']) {
            \umask(0000);
            Debug::enable();
        }

        // override the default Symfony Error Handler and use our own instead
        SymfonyErrorHandler::register(new SymfonyErrorHandler(new BufferingLogger()));

        $trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false;
        $trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false;

        $trustedHeaderSet = (Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
            ) ^ Request::HEADER_X_FORWARDED_HOST
        ;

        if ($trustedProxies) {
            Request::setTrustedProxies(\explode(',', $trustedProxies), $trustedHeaderSet);
        }

        if ($trustedHosts) {
            Request::setTrustedHosts([$trustedHosts]);
        }

        self::$kernel = new AppKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
        self::$kernel->boot();
    }

    public static function terminate(): void
    {
        if (\function_exists('fastcgi_finish_request')) {
            \fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            Response::closeOutputBuffers(0, true);
        }

        // check if a Symfony request object was set (either content element rendering or Symfony route handler)
        // (may occur for TYPO3 StaticRoutes and PageNotFoundHandler)
        if (!self::$request instanceof Request) {
            $typo3Request = $GLOBALS['TYPO3_REQUEST'] ?? null;
            // try to create Symfony request based on the TYPO3 server request
            if ($typo3Request instanceof ServerRequest) {
                self::$request = (new HttpFoundationFactory())->createRequest($typo3Request);
            } else {
                // fallback if neither the Symfony request was specified nor the TYPO3 server request is defined
                self::$request = Request::createFromGlobals();
            }
        }

        // use an empty response if none is set (may occur for TYPO3 StaticRoutes and PageNotFoundHandler)
        if (!self::$response instanceof Response) {
            self::$response = new Response();
        }

        self::$kernel->terminate(self::$request, self::$response);
    }

    /**
     * @internal
     */
    public static function setRequestForTermination(Request $request): void
    {
        self::$request = $request;
    }

    /**
     * @internal
     */
    public static function setResponseForTermination(Response $response): void
    {
        self::$response = $response;
    }
}
