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

namespace Bartacus\Bundle\BartacusBundle\Scheduler\Proxy;

use ProxyManager\Inflector\ClassNameInflectorInterface;
use ProxyManager\Inflector\Util\ParameterHasher;

final class ClassNameInflector implements ClassNameInflectorInterface
{
    /**
     * @var string
     */
    private $proxyNamespace;

    /**
     * @var int
     */
    private $proxyMarkerLength;

    /**
     * @var string
     */
    private $proxyMarker;

    /**
     * @var ParameterHasher
     */
    private $parameterHasher;

    public function __construct(string $proxyNamespace)
    {
        $this->proxyNamespace = $proxyNamespace;
        $this->proxyMarker = '\\'.static::PROXY_MARKER.'\\';
        $this->proxyMarkerLength = \mb_strlen($this->proxyMarker);
        $this->parameterHasher = new ParameterHasher();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserClassName(string $className): string
    {
        $className = \ltrim($className, '\\');

        if (false === $position = \mb_strrpos($className, $this->proxyMarker)) {
            return $className;
        }

        return \mb_substr(
            $className,
            $this->proxyMarkerLength + $position,
            \mb_strrpos($className, '\\') - ($position + $this->proxyMarkerLength)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyClassName(string $className, array $options = []): string
    {
        $shortClassName = \mb_substr($className, \mb_strrpos($className, '\\') + 1);

        return $this->proxyNamespace
            .$this->proxyMarker
            .$this->getUserClassName($className)
            .'\\Generated'.$shortClassName.$this->parameterHasher->hashParameters($options);
    }

    /**
     * {@inheritdoc}
     */
    public function isProxyClassName(string $className): bool
    {
        return false !== \mb_strrpos($className, $this->proxyMarker);
    }
}
