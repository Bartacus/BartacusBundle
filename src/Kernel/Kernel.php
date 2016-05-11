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

use Bartacus\Bundle\BartacusBundle\Typo3\Xclass\TypoScriptFrontendController;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Utility\EidUtility;

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
    const VERSION = '0.3.5';
    const VERSION_ID = '00305';
    const MAJOR_VERSION = '0';
    const MINOR_VERSION = '3';
    const RELEASE_VERSION = '5';
    const EXTRA_VERSION = '';

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
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        // we are handling the request fully in Symfony, which means we dispatched in
        // TYPO3 via eID.
        if (HttpKernelInterface::MASTER_REQUEST === $type) {
            $this->initTsfe($request);
        }

        return $this->getHttpKernel()->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function terminate(Request $request, Response $response)
    {
        parent::terminate($request, $response);

        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        $tsfe->storeSessionData();
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

    /**
     * Init the TypoScript frontend controller
     *
     * @param Request $request
     *
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    private function initTsfe(Request $request)
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = GeneralUtility::makeInstance(
            'TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController',
            $GLOBALS['TYPO3_CONF_VARS'],
            0,  // pid
            0,  // type
            0,  // no_cache
            '', // cHash
            '', // jumpurl
            '', // MP,
            ''  // RDCT
        );
        $GLOBALS['TSFE'] = $tsfe;

        // Initialize Language
        EidUtility::initLanguage();

        // Initialize FE User.
        $GLOBALS['TSFE']->initFEuser();

        // Important: no Cache for Ajax stuff
        $GLOBALS['TSFE']->set_no_cache();
        $GLOBALS['TSFE']->determineId();
        $GLOBALS['TSFE']->initTemplate();
        $GLOBALS['TSFE']->getConfigArray();
        Bootstrap::getInstance()->loadCachedTca();
        $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
        $GLOBALS['TSFE']->settingLanguage();
        $GLOBALS['TSFE']->settingLocale();

        $request->setLocale(explode('.', $GLOBALS['TSFE']->config['config']['locale_all'])[0]);
    }
}
