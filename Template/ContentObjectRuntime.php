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

namespace Bartacus\Bundle\BartacusBundle\Template;

use Twig\Extension\RuntimeExtensionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentObjectRuntime implements RuntimeExtensionInterface
{
    private array $typoScriptSetup;

    /**
     * @throws NoServerRequestGivenException
     */
    public function __construct(
        ConfigurationManagerInterface $configurationManager,
    ) {
        $this->typoScriptSetup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
    }

    /**
     * Renders the TypoScript object in the given TypoScript setup path.
     */
    public function cObject(string $typoScriptObjectPath, object|array|string $data = [], ?string $currentValueKey = null, string $table = ''): string
    {
       $currentValue = null;

        if (\is_object($data)) {
            $data = ObjectAccess::getGettableProperties($data);
        } elseif (\is_string($data)) {
            $currentValue = $data;
            $data = [$data];
        }

        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $cObj->start($data, $table);

        if (null !== $currentValue) {
            $cObj->setCurrentVal($currentValue);
        } elseif (null !== $currentValueKey && isset($data[$currentValueKey])) {
            $cObj->setCurrentVal($data[$currentValueKey]);
        }

        $pathSegments = GeneralUtility::trimExplode('.', $typoScriptObjectPath);
        $lastSegment = array_pop($pathSegments);
        $setup = $this->typoScriptSetup;

        foreach ($pathSegments as $segment) {
            if (!\array_key_exists($segment.'.', $setup)) {
                throw new \InvalidArgumentException(sprintf('TypoScript object path "%s" does not exist', $typoScriptObjectPath));
            }

            $setup = $setup[$segment.'.'];
        }

        return $cObj->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment.'.']);
    }
}
