<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) 2016-2017 Patrik Karisch
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

namespace Bartacus\Bundle\BartacusBundle\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\RequestHandler;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * This is the main entry point of the TypoScript and Symfony combined driven standard front-end.
 *
 * Basically put, this is the script which all requests for TYPO3 delivered pages goes to in the
 * frontend (the website). The script instantiates a $TSFE object, includes libraries and does a little logic here
 * and there in order to instantiate the right classes to create the webpage.
 *
 * In between it calls Symfony routes and either executes and returns them, or calls the the real TYPO3 page.
 */
class SymfonyFrontendRequestHandler extends RequestHandler
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Handles a frontend request.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface|SymfonyResponse|null
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $response = null;
        $this->request = $request;

        // yes, we must use the global declared kernel here, because the request handler is
        // initialized from the Bootstrap with no control of the constructor..
        global $kernel;
        $this->kernel = $kernel;

        $this->initializeTimeTracker();

        // Hook to preprocess the current request:
        if (\is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'] as $hookFunction) {
                $hookParameters = [];
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $hookParameters);
            }
            unset($hookFunction, $hookParameters);
        }

        $this->initializeController();

        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_force']
            && !GeneralUtility::cmpIP(
                GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])
        ) {
            $this->controller->pageUnavailableAndExit('This page is temporarily unavailable.');
        }

        $this->controller->connectToDB();
        $this->controller->sendRedirect();

        // Output compression
        // Remove any output produced until now
        $this->bootstrap->endOutputBufferingAndCleanPreviousOutput();
        $this->initializeOutputCompression();

        $this->bootstrap->loadBaseTca();

        // Initializing the Frontend User
        $this->timeTracker->push('Front End user initialized', '');
        $this->controller->initFEuser();
        $this->timeTracker->pull();

        // Initializing a possible logged-in Backend User
        /* @var $GLOBALS ['BE_USER'] \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
        $GLOBALS['BE_USER'] = $this->controller->initializeBackendUser();

        // Process the ID, type and other parameters.
        // After this point we have an array, $page in TSFE, which is the page-record
        // of the current page, $id.
        $this->timeTracker->push('Process ID', '');
        // Initialize admin panel since simulation settings are required here:
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeAdminPanel();
            $this->bootstrap
                ->initializeBackendRouter()
                ->loadExtTables()
            ;
        }

        $symfonyRequest = Request::createFromGlobals();

        try {
            $handleWithRealUrl = false;

            /** @var RequestContext $routerRequestContext */
            $routerRequestContext = $this->kernel->getContainer()->get('router.request_context');
            $routerRequestContext->fromRequest($symfonyRequest);

            /** @var Router $router */
            $router = $this->kernel->getContainer()->get('router');
            $router->matchRequest($symfonyRequest);
        } catch (ResourceNotFoundException $e) {
            $handleWithRealUrl = true;
        }

        if ($handleWithRealUrl) {
            $this->controller->checkAlternativeIdMethods();
            $symfonyRequest = Request::createFromGlobals();
        }
        $this->controller->clear_preview();
        $this->controller->determineId();

        // Now, if there is a backend user logged in and he has NO access to this page,
        // then re-evaluate the id shown! _GP('ADMCMD_noBeUser') is placed here because
        // \TYPO3\CMS\Version\Hook\PreviewHook might need to know if a backend user is logged in.
        if (
            $this->controller->isBackendUserLoggedIn()
            && (!$GLOBALS['BE_USER']->extPageReadAccess($this->controller->page)
                || GeneralUtility::_GP('ADMCMD_noBeUser'))
        ) {
            // Remove user
            unset($GLOBALS['BE_USER']);
            $this->controller->beUserLogin = false;
            // Re-evaluate the page-id.
            if ($handleWithRealUrl) {
                $this->controller->checkAlternativeIdMethods();
            }
            $this->controller->clear_preview();
            $this->controller->determineId();
        }

        $this->controller->makeCacheHash();
        $this->timeTracker->pull();

        // Admin Panel & Frontend editing
        if ($this->controller->isBackendUserLoggedIn()) {
            $GLOBALS['BE_USER']->initializeFrontendEdit();
            if ($GLOBALS['BE_USER']->adminPanel instanceof AdminPanelView) {
                $this->bootstrap->initializeLanguageObject();
            }
            if ($GLOBALS['BE_USER']->frontendEdit instanceof FrontendEditingController) {
                $GLOBALS['BE_USER']->frontendEdit->initConfigOptions();
            }
        }

        // Starts the template
        $this->timeTracker->push('Start Template', '');
        $this->controller->initTemplate();
        $this->timeTracker->pull();
        // Get from cache
        $this->timeTracker->push('Get Page from cache', '');
        $this->controller->getFromCache();
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $this->controller->getConfigArray();
        // Setting language and locale
        $this->timeTracker->push('Setting language and locale', '');
        $this->controller->settingLanguage();
        $this->controller->settingLocale();
        $this->timeTracker->pull();

        $response = $this->handleSymfonyRequest($symfonyRequest);

        $modifyContent = true;
        if ($response) {
            $sendTSFEContent = true;
            $modifyContent = false;
        } else {
            // Convert POST data to utf-8 for internal processing if metaCharset is different
            $this->controller->convPOSTCharset();

            $this->controller->initializeRedirectUrlHandlers();

            $this->controller->handleDataSubmission();

            // Check for shortcut page and redirect
            $this->controller->checkPageForShortcutRedirect();
            $this->controller->checkPageForMountpointRedirect();

            // Generate page
            $this->controller->setUrlIdToken();
            $this->timeTracker->push('Page generation', '');
            if ($this->controller->isGeneratePage()) {
                $this->controller->generatePage_preProcessing();
                $temp_theScript = $this->controller->generatePage_whichScript();
                if ($temp_theScript) {
                    include $temp_theScript;
                } else {
                    $this->controller->preparePageContentGeneration();
                    // Content generation
                    if (!$this->controller->isINTincScript()) {
                        PageGenerator::renderContent();
                        $this->controller->setAbsRefPrefix();
                    }
                }
                $this->controller->generatePage_postProcessing();
            } elseif ($this->controller->isINTincScript()) {
                $this->controller->preparePageContentGeneration();
            }
            $this->controller->releaseLocks();
            $this->timeTracker->pull();

            // Render non-cached parts
            if ($this->controller->isINTincScript()) {
                $this->timeTracker->push('Non-cached objects', '');
                $this->controller->INTincScript();
                $this->timeTracker->pull();
            }

            // Output content
            $sendTSFEContent = false;
            if ($this->controller->isOutputting()) {
                $this->timeTracker->push('Print Content', '');
                $this->controller->processOutput();
                $sendTSFEContent = true;
                $this->timeTracker->pull();
            }
        }

        // Store session data for fe_users
        $this->controller->storeSessionData();
        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = \microtime(true);
        if ($modifyContent && $this->controller->isOutputting()) {
            if (isset($this->controller->config['config']['debug'])) {
                $debugParseTime = (bool) $this->controller->config['config']['debug'];
            } else {
                $debugParseTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
            }
            if ($debugParseTime) {
                $this->controller->content .= LF.'<!-- Parsetime: '.$this->getParseTime().'ms -->';
            }
        }
        $this->controller->redirectToExternalUrl();
        // Preview info
        if ($modifyContent) {
            $this->controller->previewInfo();
        }
        // Hook for end-of-frontend
        $this->controller->hook_eofe();
        // Finish timetracking
        $this->timeTracker->pull();

        // Admin panel
        if ($modifyContent
            && $this->controller->isBackendUserLoggedIn()
            && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication
        ) {
            if ($GLOBALS['BE_USER']->isAdminPanelVisible()) {
                $this->controller->content = \str_ireplace('</body>', $GLOBALS['BE_USER']->displayAdminPanel().'</body>',
                    $this->controller->content);
            }
        }

        if ($sendTSFEContent) {
            if (!$response) {
                /** @var Response $response */
                $response = GeneralUtility::makeInstance(Response::class);
                $response->getBody()->write($this->controller->content);
            } elseif ($modifyContent && $response instanceof ResponseInterface) {
                $response->getBody()->close();

                $body = new Stream('php://temp', 'rw');
                $body->write($this->controller->content);
                $response = $response->withBody($body);
            }
        }

        // Send content-length header.
        // Notice that all HTML content outside the length of the content-length header will be cut off!
        // Therefore content of unknown length from included PHP-scripts and if admin users are logged
        // in (admin panel might show...) or if debug mode is turned on, we disable it!
        if ((
                !isset($this->controller->config['config']['enableContentLengthHeader'])
                || $this->controller->config['config']['enableContentLengthHeader']
            )
            && !$this->controller->beUserLogin
            && !$GLOBALS['TYPO3_CONF_VARS']['FE']['debug']
            && !$this->controller->config['config']['debug']
            && !$this->controller->doWorkspacePreview()
            && $response instanceof ResponseInterface
        ) {
            $contentLength = $response->getBody()->getSize();
            if (null !== $contentLength) {
                $response = $response->withAddedHeader('Content-Length', (string) $contentLength);
            }
        }

        // Debugging Output
        if (isset($GLOBALS['error']) && \is_object($GLOBALS['error'])
            && @\is_callable([$GLOBALS['error'], 'debugOutput'])
        ) {
            $GLOBALS['error']->debugOutput();
        }
        GeneralUtility::devLog('END of FRONTEND session', 'cms', 0, ['_FLUSH' => true]);

        return $response;
    }

    /**
     * @throws \Exception
     */
    protected function handleSymfonyRequest(Request $symfonyRequest): ?SymfonyResponse
    {
        $this->timeTracker->push('Symfony request handling');

        // set the locale from TypoScript, effectively killing _locale from router :/
        [$locale] = \explode('.', $this->controller->config['config']['locale_all']);
        $symfonyRequest->attributes->set('_locale', $locale);

        $response = null;

        try {
            $response = $this->kernel->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);
            $symfonyResponseContentType = $response->headers->get('Content-Type');

            // write content to the TSFE if a simple response with HTTP 200 and if html
            if (!$response instanceof BinaryFileResponse
                && !$response instanceof StreamedResponse
                && SymfonyResponse::HTTP_OK === $response->getStatusCode()
                && null !== $symfonyResponseContentType
                && 0 === \mb_stripos($symfonyResponseContentType, 'text/html')
            ) {
                $this->controller->content = $response->getContent();
            }

            FrontendApplication::setRequestResponseForTermination($symfonyRequest, $response, true);
        } catch (NotFoundHttpException $e) {
            // We only want to match if the route was not found. Other errors should output. Ugly hack.
            if (0 !== \mb_strpos($e->getMessage(), 'No route found for')) {
                throw $e;
            }

            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->kernel->getContainer()->get('event_dispatcher');

            /** @var RequestStack $requestStack */
            $requestStack = $this->kernel->getContainer()->get('request_stack');

            /** @var HttpKernelInterface $httpKernel */
            $httpKernel = $this->kernel->getContainer()->get('http_kernel');

            // no route found, but to initialize locale and translator correctly
            // dispatch request event again, but skip router.
            $symfonyRequest->attributes->set('_controller', 'typo3');

            // add back to request stack, because the finishRequest after exception popped.
            $requestStack->push($symfonyRequest);

            $event = new GetResponseEvent($httpKernel, $symfonyRequest, HttpKernelInterface::MASTER_REQUEST);
            $eventDispatcher->dispatch(KernelEvents::REQUEST, $event);

            // Aaaaaaaaaand another ugly hack for the kernel termination :/
            $symfonyResponse = new SymfonyResponse($e->getMessage(), 404);

            FrontendApplication::setRequestResponseForTermination($symfonyRequest, $symfonyResponse, false);
        }

        $this->timeTracker->pull();

        return $response;
    }
}
