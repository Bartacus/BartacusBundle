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

declare(strict_types = 1);

namespace Bartacus\Bundle\BartacusBundle\Http;

use Bartacus\Bundle\BartacusBundle\Http\Factory\Typo3PsrMessageFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\MonitorUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\Utility\CompressionUtility;
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
class SymfonyFrontendRequestHandler implements RequestHandlerInterface
{
    /**
     * Instance of the current TYPO3 bootstrap.
     *
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Instance of the timetracker.
     *
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * Instance of the TSFE object.
     *
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * The request handed over.
     *
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * Constructor handing over the bootstrap and the original request.
     *
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Handles a frontend request.
     *
     * @param ServerRequestInterface $request
     *
     * @return null|ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ? ResponseInterface
    {
        $response = null;
        $this->request = $request;

        // yes, we must use the global declared kernel here, because the request handler is
        // initialized from the Bootstrap with no control of the constructor..
        /* @var KernelInterface $kernel */
        global $kernel;
        $this->kernel = $kernel;

        $this->initializeTimeTracker();

        // Hook to preprocess the current request:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'])) {
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

        $httpFoundationFactory = new HttpFoundationFactory();
        $symfonyRequest = $httpFoundationFactory->createRequest($request);

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
            $request = ServerRequestFactory::fromGlobals();
            $symfonyRequest = $httpFoundationFactory->createRequest($request);
        }
        $this->controller->clear_preview();
        $this->controller->determineId();

        // Now, if there is a backend user logged in and he has NO access to this page,
        // then re-evaluate the id shown! _GP('ADMCMD_noBeUser') is placed here because
        // \TYPO3\CMS\Version\Hook\PreviewHook might need to know if a backend user is logged in.
        if (
            $this->controller->isBackendUserLoggedIn()
            && (!$GLOBALS['BE_USER']->extPageReadAccess($this->controller->page) || GeneralUtility::_GP('ADMCMD_noBeUser'))
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
                    PageGenerator::pagegenInit();
                    // Global content object
                    $this->controller->newCObj();
                    // Content generation
                    if (!$this->controller->isINTincScript()) {
                        PageGenerator::renderContent();
                        $this->controller->setAbsRefPrefix();
                    }
                }
                $this->controller->generatePage_postProcessing();
            } elseif ($this->controller->isINTincScript()) {
                PageGenerator::pagegenInit();
                // Global content object
                $this->controller->newCObj();
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
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);
        $this->controller->setParseTime();
        if (isset($this->controller->config['config']['debug'])) {
            $debugParseTime = (bool) $this->controller->config['config']['debug'];
        } else {
            $debugParseTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        }
        if ($modifyContent && $this->controller->isOutputting() && $debugParseTime) {
            $this->controller->content .= LF.'<!-- Parsetime: '.$this->controller->scriptParseTime.'ms -->';
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
        // Check memory usage
        MonitorUtility::peakMemoryUsage();
        // beLoginLinkIPList
        if ($modifyContent) {
            echo $this->controller->beLoginLinkIPList();
        }

        // Admin panel
        if ($modifyContent && $this->controller->isBackendUserLoggedIn() && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            if ($GLOBALS['BE_USER']->isAdminPanelVisible()) {
                $this->controller->content = str_ireplace('</body>', $GLOBALS['BE_USER']->displayAdminPanel().'</body>',
                    $this->controller->content);
            }
        }

        if ($sendTSFEContent) {
            if (!$response) {
                /** @var Response $response */
                $response = GeneralUtility::makeInstance(Response::class);
                $response->getBody()->write($this->controller->content);
            } elseif ($modifyContent) {
                $response->getBody()->close();

                $body = new Stream('php://temp', 'rw');
                $body->write($this->controller->content);
                $response = $response->withBody($body);
            }
        }
        // Debugging Output
        if (isset($GLOBALS['error']) && is_object($GLOBALS['error']) && @is_callable([
                $GLOBALS['error'],
                'debugOutput',
            ])
        ) {
            $GLOBALS['error']->debugOutput();
        }
        if (TYPO3_DLOG) {
            GeneralUtility::devLog('END of FRONTEND session', 'cms', 0, ['_FLUSH' => true]);
        }

        return $response;
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request
     *
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return $request->getQueryParams()['eID'] || $request->getParsedBody()['eID'] ? false : true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Initializes output compression when enabled, could be split up and put into Bootstrap
     * at a later point.
     */
    protected function initializeOutputCompression(): void
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] && extension_loaded('zlib')) {
            if (MathUtility::canBeInterpretedAsInteger($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'])) {
                @ini_set('zlib.output_compression_level', $GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']);
            }
            ob_start([GeneralUtility::makeInstance(CompressionUtility::class), 'compressionOutputHandler']);
        }
    }

    /**
     * Timetracking started depending if a Backend User is logged in.
     */
    protected function initializeTimeTracker(): void
    {
        $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']) ?: 'be_typo_user';

        /** @var TimeTracker timeTracker */
        $this->timeTracker = GeneralUtility::makeInstance(
            TimeTracker::class,
            ($this->request->getCookieParams()[$configuredCookieName] ? true : false)
        );
        $this->timeTracker->start();
    }

    /**
     * Creates an instance of TSFE and sets it as a global variable.
     */
    protected function initializeController(): void
    {
        $this->controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            GeneralUtility::_GP('id'),
            GeneralUtility::_GP('type'),
            GeneralUtility::_GP('no_cache'),
            GeneralUtility::_GP('cHash'),
            null,
            GeneralUtility::_GP('MP'),
            GeneralUtility::_GP('RDCT')
        );
        // setting the global variable for the controller
        // We have to define this as reference here, because there is code around
        // which exchanges the TSFE object in the global variable. The reference ensures
        // that the $controller member always works on the same object as the global variable.
        // This is a dirty workaround and bypasses the protected access modifier of the controller member.
        $GLOBALS['TSFE'] = &$this->controller;
    }

    /**
     * @throws \Exception
     */
    protected function handleSymfonyRequest(Request $symfonyRequest): ? ResponseInterface
    {
        $this->timeTracker->push('Symfony request handling', '');

        // set the locale from TypoScript, effectively killing _locale from router :/
        $symfonyRequest->attributes->set('_locale', $this->controller->sys_language_isocode);

        // yes, we must use the global declared kernel here, because the request handler is
        // initialized from the Bootstrap with no control of the constructor..
        /* @var KernelInterface $kernel */
        global $kernel;

        $response = null;
        try {
            $symfonyResponse = $kernel->handle($symfonyRequest, HttpKernelInterface::MASTER_REQUEST, false);

            if (!$symfonyResponse instanceof BinaryFileResponse
                && !$symfonyResponse instanceof StreamedResponse
                && 0 === stripos($symfonyResponse->headers->get('Content-Type'), 'text/html')
            ) {
                $this->controller->content = $symfonyResponse->getContent();
            }

            $psr7Factory = new Typo3PsrMessageFactory();
            $response = $psr7Factory->createResponse($symfonyResponse);
        } catch (NotFoundHttpException $e) {
            // We only want to match if the route was not found. Other errors should output. Ugly hack.
            if (0 !== strpos($e->getMessage(), 'No route found for')) {
                throw $e;
            }

            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $kernel->getContainer()->get('event_dispatcher');

            /** @var RequestStack $requestStack */
            $requestStack = $kernel->getContainer()->get('request_stack');

            /** @var HttpKernelInterface $httpKernel */
            $httpKernel = $kernel->getContainer()->get('http_kernel');

            // no route found, but to initialize locale and translator correctly
            // dispatch request event again, but skip router.
            $symfonyRequest->attributes->set('_controller', 'typo3');

            // add back to request stack, because the finishRequest after exception popped.
            $requestStack->push($symfonyRequest);

            $event = new GetResponseEvent($httpKernel, $symfonyRequest, HttpKernelInterface::MASTER_REQUEST);
            $eventDispatcher->dispatch(KernelEvents::REQUEST, $event);

            // Aaaaaaaaaand another ugly hack for the kernel termination :/
            $symfonyResponse = new SymfonyResponse($e->getMessage(), 404);
        }

        FrontendApplication::setRequestResponseForTermination($symfonyRequest, $symfonyResponse);
        $this->timeTracker->pull();

        return $response;
    }
}
