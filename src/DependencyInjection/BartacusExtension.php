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

namespace Bartacus\Bundle\BartacusBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads all necessary container configuration
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->addTypo3ConfVar($container, $GLOBALS['TYPO3_CONF_VARS'], 'typo3_conf_vars');

        if (isset($config['plugins'])) {
            $this->registerRouterConfiguration($config['plugins'], $container, $loader);
        }

        if (isset($config['dispatch_uris'])) {
            $container->setParameter('bartacus.dispatch_uris', $config['dispatch_uris']);
        }
    }

    /**
     * Add a part of the typo3 conf vars to the container.
     *
     * @param ContainerBuilder $container
     * @param mixed            $typoConfVars
     * @param string           $baseName The recursive base name of the parameters
     */
    private function addTypo3ConfVar(ContainerBuilder $container, $typoConfVars, $baseName)
    {
        $container->setParameter($baseName, $typoConfVars);

        if (is_array($typoConfVars)) {
            foreach ($typoConfVars as $key => $typoConfVar) {
                $this->addTypo3ConfVar($container, $typoConfVar, $baseName.'.'.$key);
            }
        }
    }

    /**
     * Loads the router configuration.
     *
     * @param array            $config    A router configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader    $loader    An XmlFileLoader instance
     */
    private function registerRouterConfiguration(array $config, ContainerBuilder $container, XmlFileLoader $loader)
    {
        $loader->load('routing.xml');

        $container->setParameter('router.plugins.resource', $config['resource']);
        $router = $container->findDefinition('router.plugins');
        $argument = $router->getArgument(2);
        $argument['strict_requirements'] = $config['strict_requirements'];
        if (isset($config['type'])) {
            $argument['resource_type'] = $config['type'];
        }
        $router->replaceArgument(2, $argument);
    }
}
