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

namespace Bartacus\Bundle\BartacusBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_METHOD)]
class ContentElement
{
    public const RESPONSE_CACHE_HEADER = 'cache_tags';

    private ?string $name;
    private bool $cached;
    private bool $useCustomCache;
    private ?int $customCacheLifetime;
    private array $customCacheTags;

    public function __construct(?string $name = null, bool $cached = true, bool $useCustomCache = false, ?int $customCacheLifetime = null, array $customCacheTags = [])
    {
        $this->name = $name;
        $this->cached = $cached;
        $this->useCustomCache = $useCustomCache;
        $this->customCacheLifetime = $customCacheLifetime;
        $this->customCacheTags = $customCacheTags;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isCached(): bool
    {
        return $this->cached;
    }

    public function usesCustomCache(): bool
    {
        return $this->useCustomCache;
    }

    public function getCustomCacheLifetime(): ?int
    {
        return $this->customCacheLifetime;
    }

    public function getCustomCacheTags(): array
    {
        return $this->customCacheTags;
    }
}
