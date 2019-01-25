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
        // Load cached env vars if the .env.local.php file exists
        // Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
        if (\is_array($env = @include \dirname(__DIR__).'/.env.local.php')) {
            $_SERVER += $env;
            $_ENV += $env;
        } elseif (!\class_exists(Dotenv::class)) {
            throw new \RuntimeException('Please run "composer require symfony/dotenv" to load the ".env" files configuring the application.');
        } else {
            // load all the .env files
            (new Dotenv())->loadEnv(\dirname(__DIR__).'/.env');
        }

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
        $_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $_SERVER['APP_DEBUG'] || \filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

        if ($_SERVER['APP_DEBUG']) {
            \umask(0000);

            Debug::enable();
        }

        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
            Request::setTrustedProxies(
                \explode(',', $trustedProxies),
                Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST
            );
        }

        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
            Request::setTrustedHosts(\explode(',', $trustedHosts));
        }

        self::$kernel = new AppKernel((string) $_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
        self::$kernel->boot();
    }
}
