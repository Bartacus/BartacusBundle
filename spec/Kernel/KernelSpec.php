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

namespace spec\Bartacus\Bundle\BartacusBundle\Kernel;

use Bartacus\Bundle\BartacusBundle\Kernel\Kernel;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Config\Loader\LoaderInterface;

// define PATH_site as fake for typo3 one.
define('PATH_site', '/tmp/');

/**
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class KernelSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf('spec\Bartacus\Bundle\BartacusBundle\Kernel\SpecKernel');
        $this->beConstructedWith('Production/Staging', true);
    }

    public function it_should_extend_symfony_kernel()
    {
        $this->shouldBeAnInstanceOf('Symfony\Component\HttpKernel\Kernel');
    }

    public function it_should_return_cache_dir_normalized()
    {
        $this->getCacheDir()->shouldBeNormalizedAndContain('typo3temp/ProductionStaging');
    }

    public function it_should_return_log_dir_normalized()
    {
        $this->getLogDir()->shouldBeNormalizedAndContain('typo3temp/logs');
    }

    public function it_should_load_config_file_normalized(LoaderInterface $loader)
    {
        $loader->load($this->getWrappedObject()->getRootDir().'/config/config_production_staging.yml')->shouldBeCalled(
        );

        $this->registerContainerConfiguration($loader);
    }

    /**
     * Add custom matchers
     *
     * @return array
     */
    public function getMatchers()
    {
        return [
            'beNormalizedAndContain' => function ($subject, $path) {
                return $this->normalizePath($subject) === $subject && false !== strpos($subject, $path);
            }
        ];
    }

    /**
     * Normalize a path. This replaces backslashes with slashes, removes ending
     * slash and collapses redundant separators and up-level references.
     *
     * @param  string $path Path to the file or directory
     *
     * @return string
     */
    private function normalizePath($path)
    {
        $parts = [];
        $path = strtr($path, '\\', '/');
        $prefix = '';
        $absolute = false;

        if (preg_match('{^([0-9a-z]+:(?://(?:[a-z]:)?)?)}i', $path, $match)) {
            $prefix = $match[1];
            $path = substr($path, strlen($prefix));
        }

        if (substr($path, 0, 1) === '/') {
            $absolute = true;
            $path = substr($path, 1);
        }

        $up = false;
        foreach (explode('/', $path) as $chunk) {
            if ('..' === $chunk && ($absolute || $up)) {
                array_pop($parts);
                $up = !(empty($parts) || '..' === end($parts));
            } elseif ('.' !== $chunk && '' !== $chunk) {
                $parts[] = $chunk;
                $up = '..' !== $chunk;
            }
        }

        return $prefix.($absolute ? '/' : '').implode('/', $parts);
    }
}

/**
 * Must be created to spec the abstract class
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class SpecKernel extends Kernel
{
    /**
     * {@inheritDoc}
     */
    public function registerBundles()
    {
        return [];
    }
}
