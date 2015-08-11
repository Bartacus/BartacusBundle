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

namespace Bartacus\Bundle\BartacusBundle\Typo3\Xclass;

use Symfony\Bundle\FrameworkBundle\EventListener\SessionListener;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\EventListener\TranslatorListener;
use Symfony\Component\HttpKernel\HttpKernel;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController as BaseTypoScriptFrontendController;

/**
 * Hook into some parts of the TSFE rendering.
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class TypoScriptFrontendController extends BaseTypoScriptFrontendController
{
    /**
     * {@inheritDoc}
     */
    public function setUrlIdToken()
    {
        $this->populateKernelRequestEvent();

        parent::setUrlIdToken();
    }

    /**
     * Execute kernel.request event to populate session and locale/translator
     */
    protected function populateKernelRequestEvent()
    {
        /** @var Container $container */
        $container = $GLOBALS['container'];

        /** @var HttpKernel $kernel */
        $kernel = $container->get('http_kernel');

        $request = Request::createFromGlobals();
        $this->addLocaleToRequest($request);

        $request->headers->set('X-Php-Ob-Level', ob_get_level());
        $container->get('request_stack')->push($request);

        $event = new GetResponseEvent($kernel, $request, HttpKernel::MASTER_REQUEST);

        /** @var SessionListener $sessionListener */
        $sessionListener = $container->get('session_listener');
        $sessionListener->onKernelRequest($event);

        /** @var LocaleListener $localeListener */
        $localeListener = $container->get('locale_listener');
        $localeListener->onKernelRequest($event);

        /** @var TranslatorListener $translatorListener */
        $translatorListener = $container->get('translator_listener');
        $translatorListener->onKernelRequest($event);
    }

    /**
     * Enumerates the locale from the TSFE and add it to the request
     *
     * @param Request $request
     */
    protected function addLocaleToRequest(Request $request)
    {
        $request->setLocale($this->config['config']['language']);
    }
}
