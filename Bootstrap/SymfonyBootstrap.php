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
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

final class SymfonyBootstrap
{
    /**
     * @var Kernel
     */
    private static $kernel;

    public static function getKernel(): Kernel
    {
        return self::$kernel;
    }

    public static function initKernel(): void
    {
        if (isset($_SERVER['SYMFONY_ENV'])) {
            @\trigger_error('The SYMFONY_ENV environment variable is deprecated since version 1.2 and will be removed in 2.0. Use APP_ENV instead.', E_USER_DEPRECATED);
        }

        if (isset($_SERVER['SYMFONY_DEBUG'])) {
            @\trigger_error('The SYMFONY_DEBUG environment variable is deprecated since version 1.2 and will be removed in 2.0. Use APP_DEBUG instead.', E_USER_DEPRECATED);
        }

        // The check is to ensure we don't use .env in production
        if (!isset($_SERVER['APP_ENV']) && !isset($_SERVER['SYMFONY_ENV'])) {
            if (!\class_exists(Dotenv::class)) {
                throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
            }
            (new Dotenv())->load(__DIR__.'/../.env');
        }

        $env = $_SERVER['APP_ENV'] ?? $_SERVER['SYMFONY_ENV'] ?? 'dev';
        $debug = $_SERVER['APP_DEBUG'] ?? $_SERVER['SYMFONY_DEBUG'] ?? ('prod' !== $env);

        if ($debug) {
            \umask(0000);

            Debug::enable();
        }

        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
            Request::setTrustedProxies(
                \explode(',', $trustedProxies),
                Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
            );
        }

        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts(\explode(',', $trustedHosts));
        }

        self::$kernel = new AppKernel((string) $env, (bool) $debug);
        self::$kernel->boot();

        /* @deprecated will be removed in 2.0, use SymfonyBootstrap::getKernel() instead */
        $GLOBALS['kernel'] = self::$kernel;
    }

    /**
     * @deprecated not used anymore, deprecated in 1.2 and will be removed in 2.0
     */
    public static function initAppPackage(): void
    {
        @\trigger_error('The SymfonyBootstrap::initAppPackage() is deprecated since version 1.2 and will be removed in 2.0. It is not used with Symfony 4 anymore', E_USER_DEPRECATED);
    }
}
