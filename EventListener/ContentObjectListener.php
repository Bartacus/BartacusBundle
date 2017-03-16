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

namespace Bartacus\Bundle\BartacusBundle\EventListener;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpKernel\KernelEvents;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Initializes the cObj on the TSFE, when not done yet.
 *
 * @DI\Service()
 */
class ContentObjectListener
{
    /**
     * @var TypoScriptFrontendController
     */
    private $frontendController;

    /**
     * @DI\InjectParams(params={
     *      "frontendController" = @DI\Inject("typo3.frontend_controller")
     * })
     */
    public function __construct(TypoScriptFrontendController $frontendController)
    {
        $this->frontendController = $frontendController;
    }

    /**
     * @DI\Observe(KernelEvents::REQUEST, priority=8)
     */
    public function onKernelRequest(): void
    {
        if (!$this->frontendController->cObj instanceof ContentObjectRenderer) {
            $this->frontendController->newCObj();
        }
    }
}
