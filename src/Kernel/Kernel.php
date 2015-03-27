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
abstract class Kernel extends BaseKernel {

	const VERSION = '0.1.0-DEV';
	const VERSION_ID = '00100';
	const MAJOR_VERSION = '0';
	const MINOR_VERSION = '1';
	const RELEASE_VERSION = '0';
	const EXTRA_VERSION = 'DEV';

	/**
	 * {@inheritdoc}
	 */
	public function __construct($environment, $debug) {
		$environment = str_replace('/', '', $environment);

		parent::__construct($environment, $debug);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return void
	 */
	public function boot() {
		parent::boot();

		$GLOBALS['container'] = $this->getContainer();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCacheDir() {
		return $this->normalizePath($this->rootDir . '/../typo3temp/' . $this->environment);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLogDir() {
		return $this->normalizePath($this->getRootDir() . '/../typo3temp/logs');
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return void
	 */
	public function registerContainerConfiguration(LoaderInterface $loader) {
		// transform CamelCase to underscore_case, 'cause Typo3 environments are
		// e.g. Development or Production/Staging, but the / is dropped by us.
		$environment = strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $this->getEnvironment()));

		$loader->load($this->getRootDir() . '/config/config_' . $environment . '.yml');
	}

	/**
	 * Normalize a path. This replaces backslashes with slashes, removes ending
	 * slash and collapses redundant separators and up-level references.
	 *
	 * @param  string $path Path to the file or directory
	 * @return string
	 */
	private function normalizePath($path) {
		$parts = array();
		$path = strtr($path, '\\', '/');
		$prefix = '';
		$absolute = FALSE;

		if (preg_match('{^([0-9a-z]+:(?://(?:[a-z]:)?)?)}i', $path, $match)) {
			$prefix = $match[1];
			$path = substr($path, strlen($prefix));
		}

		if (substr($path, 0, 1) === '/') {
			$absolute = TRUE;
			$path = substr($path, 1);
		}

		$up = FALSE;
		foreach (explode('/', $path) as $chunk) {
			if ('..' === $chunk && ($absolute || $up)) {
				array_pop($parts);
				$up = !(empty($parts) || '..' === end($parts));
			} elseif ('.' !== $chunk && '' !== $chunk) {
				$parts[] = $chunk;
				$up = '..' !== $chunk;
			}
		}

		return $prefix . ($absolute ? '/' : '') . implode('/', $parts);
	}
}
