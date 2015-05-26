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

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * The base plugin for Typo3 plugins to fly to the sky ;)
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
abstract class Plugin extends AbstractPlugin
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $content;

    /**
     * typo3 main plugin entry point
     *
     * @param string $content The plugin content
     * @param array  $conf    The plugin configuration
     *
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        $this->container = $GLOBALS['container'];
        $this->content = $content;
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();

        return $this->execute();
    }

    /**
     * Execute the plugin, e.g. retrieve data, render it's content..
     *
     * @return string The content that is displayed on the website
     */
    abstract protected function execute();

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    protected function render($view, array $parameters = [])
    {
        return $this->container->get('templating')->render($view, $parameters);
    }

    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return bool TRUE if the service id is defined, FALSE otherwise
     */
    protected function has($id)
    {
        return $this->container->has($id);
    }

    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Returns true if the parameter name is defined.
     *
     * @param string $name The parameter name
     *
     * @return bool TRUE if the parameter name is defined, FALSE otherwise
     */
    protected function hasParameter($name)
    {
        return $this->container->hasParameter($name);
    }

    /**
     * Gets a parameter by name.
     *
     * @param string $name The parameter name
     *
     * @return mixed The parameter
     */
    protected function getParameter($name)
    {
        return $this->container->getParameter($name);
    }
}
