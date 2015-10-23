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

namespace Bartacus\Bundle\BartacusBundle\Kernel;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * The kernel is the heart of the Typo3 Symfony integration.
 *
 * It manages an environment made of bundles.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 *
 * @api
 */
abstract class Kernel extends BaseKernel
{
    const VERSION = '0.3.3-DEV';
    const VERSION_ID = '00303';
    const MAJOR_VERSION = '0';
    const MINOR_VERSION = '3';
    const RELEASE_VERSION = '3';
    const EXTRA_VERSION = 'DEV';

    /**
     * {@inheritdoc}
     */
    public function __construct($environment, $debug)
    {
        $environment = str_replace('/', '', $environment);

        parent::__construct($environment, $debug);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $GLOBALS['container'] = $this->getContainer();
        $GLOBALS['kernel'] = $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return PATH_site.'typo3temp/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return PATH_site.'typo3temp/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Bartacus\Bundle\BartacusBundle\BartacusBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        // transform CamelCase to underscore_case, 'cause Typo3 environments are
        // e.g. Development or Production/Staging, but the / is dropped by us.
        $environment = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $this->getEnvironment()));

        $loader->load($this->getRootDir().'/config/config_'.$environment.'.yml');
    }
}
