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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Service bridge to TYPO3 instantiation and global instances.
 */
class ServiceBridge
{
    /**
     * @var ObjectManagerInterface
     */
    private $extbaseObjectManager;

    /**
     * Wrapper around {@see GeneralUtility::makeInstance()}.
     *
     * @param string $className
     *
     * @return object
     */
    public function makeInstance(string $className)
    {
        return GeneralUtility::makeInstance($className);
    }

    public function getExtbaseInstance(string $objectName)
    {
        if (null === $this->extbaseObjectManager) {
            $this->extbaseObjectManager = $this->makeInstance(ObjectManager::class);
        }

        return $this->extbaseObjectManager->get($objectName);
    }

    /**
     * Get a TYPO3 global into the service container.
     *
     * @param string $global
     *
     * @return mixed
     */
    public function getGlobal(string $global)
    {
        return $GLOBALS[$global];
    }

    /**
     * @return ContentObjectRenderer
     */
    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = $this->getGlobal('TSFE');

        return $frontendController->cObj;
    }

    /**
     * @return PageRepository
     */
    public function getPageRepository(): PageRepository
    {
        /** @var TypoScriptFrontendController $frontendController */
        $frontendController = $this->getGlobal('TSFE');

        return $frontendController->sys_page;
    }
}
