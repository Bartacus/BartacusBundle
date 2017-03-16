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

namespace Bartacus\Bundle\BartacusBundle\CacheWarmer;

use Bartacus\Bundle\BartacusBundle\ContentElement\ContentElementConfigLoader;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;

/**
 * @DI\Service("bartacus.content_element.cache_warmer")
 * @DI\Tag("kernel.cache_warmer")
 */
class ContentElementCacheWarmer implements CacheWarmerInterface
{
    protected $configLoader;

    /**
     * @DI\InjectParams(params={
     *      "configLoader" = @DI\Inject("bartacus.content_element.config_loader")
     * })
     */
    public function __construct(ContentElementConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir): void
    {
        if ($this->configLoader instanceof WarmableInterface) {
            $this->configLoader->warmUp($cacheDir);
        }
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return bool always true
     */
    public function isOptional(): bool
    {
        return true;
    }
}
