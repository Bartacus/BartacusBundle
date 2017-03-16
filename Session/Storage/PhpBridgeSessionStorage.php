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

namespace Bartacus\Bundle\BartacusBundle\Session\Storage;

use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Allows session to be started either by PHP or by Symfony and managed by Symfony.
 */
class PhpBridgeSessionStorage extends NativeSessionStorage
{
    /**
     * {@inheritdoc}
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $shouldStart = true;
        if (\PHP_SESSION_ACTIVE === session_status()) {
            $shouldStart = false;
        }

        if (ini_get('session.use_cookies') && headers_sent($file, $line)) {
            throw new \RuntimeException(sprintf('Failed to start the session because headers have already been sent by "%s" at line %d.', $file, $line));
        }

        // ok to try and start the session
        if ($shouldStart && !session_start()) {
            throw new \RuntimeException('Failed to start the session');
        }

        $this->loadSession();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        // clear out the bags and nothing else that may be set
        // since the purpose of this driver is to share a handler
        foreach ($this->bags as $bag) {
            $bag->clear();

            $key = $bag->getStorageKey();
            $_SESSION[$key] = [];
        }

        // reconnect the bags to the session
        $this->loadSession();
    }
}
