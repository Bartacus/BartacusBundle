<?php

declare(strict_types=1);

/*
 * This file is part of the Bartacus project, which integrates Symfony into TYPO3.
 *
 * Copyright (c) Emily Karisch
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

namespace Bartacus\Bundle\BartacusBundle\ServiceBridge;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Service bridge to TYPO3 instantiation and global instances.
 */
class ServiceBridge
{
    public function makeInstance(string $className): ?object
    {
        return GeneralUtility::makeInstance($className);
    }

    public function getExtbaseInstance(string $objectName): ?object
    {
        return GeneralUtility::makeInstance($objectName);
    }

    public function getGlobal(string $global): mixed
    {
        return $GLOBALS[$global] ?? null;
    }

    public function getLanguageService(): ?LanguageService
    {
        return $this->getGlobal('LANG');
    }

    public function getBackendUser(): ?BackendUserAuthentication
    {
        return $this->getGlobal('BE_USER');
    }

    public function getFrontendUser(): ?FrontendUserAuthentication
    {
        $request = $this->getGlobal('TYPO3_REQUEST');

        if ($request instanceof ServerRequestInterface) {
            return $request->getAttribute('frontend.user');
        }

        return null;
    }

    public function getContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->makeInstance(ContentObjectRenderer::class);
    }
}
