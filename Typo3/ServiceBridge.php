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

namespace Bartacus\Bundle\BartacusBundle\Typo3;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Service bridge to TYPO3 instantiation and global instances.
 */
class ServiceBridge
{
    /**
     * Wrapper around {@see GeneralUtility::makeInstance()}.
     */
    public function makeInstance(string $className): ?object
    {
        return GeneralUtility::makeInstance($className);
    }

    public function getExtbaseInstance(string $objectName): ?object
    {
        return GeneralUtility::makeInstance($objectName);
    }

    /**
     * Get a TYPO3 global into the service container.
     */
    public function getGlobal(string $global): mixed
    {
        return $GLOBALS[$global];
    }

    public function getFrontendController(): ?TypoScriptFrontendController
    {
        return $this->getGlobal('TSFE');
    }

    public function getContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->getFrontendController()?->cObj;
    }

    public function getPageRepository(): ?PageRepository
    {
        return $this->getFrontendController()?->sys_page;
    }

    public function getFrontendUser(): ?FrontendUserAuthentication
    {
        return $this->getFrontendController()?->fe_user instanceof FrontendUserAuthentication? $this->getFrontendController()->fe_user : null;
    }
}
