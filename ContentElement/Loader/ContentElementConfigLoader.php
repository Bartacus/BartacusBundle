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

namespace Bartacus\Bundle\BartacusBundle\ContentElement\Loader;

use Bartacus\Bundle\BartacusBundle\Attribute\ContentElement;
use Bartacus\Bundle\BartacusBundle\ContentElement\Renderer;
use ReflectionException;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class ContentElementConfigLoader implements WarmableInterface
{
    private array $classnames;
    private ?string $cacheDir;
    private bool $debug;
    private bool $typoScriptLoaded = false;
    private ?ConfigCacheFactoryInterface $configCacheFactory = null;

    public function __construct(array $classnames, string $cacheDir = null, bool $debug = false)
    {
        $this->classnames = $classnames;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    public function setConfigCacheFactory(ConfigCacheFactoryInterface $configCacheFactory): void
    {
        $this->configCacheFactory = $configCacheFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $currentDir = $this->cacheDir;

        // force cache generation
        $this->cacheDir = $cacheDir;
        $this->loadTypoScript();

        $this->cacheDir = $currentDir;

        // No need to preload anything
        return [];
    }

    public function load(): void
    {
        if (true === $this->typoScriptLoaded) {
            return;
        }

        ExtensionManagementUtility::addTypoScript(
            'Bartacus',
            'setup',
            $this->loadTypoScript()
        );

        $this->typoScriptLoaded = true;
    }

    private function loadTypoScript(): string
    {
        $typoscriptContent = $this->concatenateTypoScript();

        if (null === $this->cacheDir) {
            return $typoscriptContent;
        }

        $cache = $this->getConfigCacheFactory()
            ->cache(
                $this->cacheDir.'/content_elements.typoscript',
                function (ConfigCacheInterface $cache) use ($typoscriptContent) {
                    $cache->write($typoscriptContent);
                }
            )
        ;

        return \file_get_contents($cache->getPath());
    }

    private function concatenateTypoScript(): string
    {
        $startingConfig = /* @lang TYPO3_TypoScript */ <<<'EOTS'
# Clear out any constants in this reserved room!
bartacus.content >

# Get content
bartacus.content.get = CONTENT
bartacus.content.get {
    table = tt_content
    select.orderBy = sorting
    select.where = colPos=0
}

# tt_content is started
tt_content >
tt_content = CASE
tt_content.key.field = CType

EOTS;

        return $startingConfig.implode("\n\n", $this->getPluginTypoScripts());
    }

    private function getPluginTypoScripts(): array
    {
        $typoscript = [];

        foreach ($this->classnames as $classname) {
            try {
                $reflectionClass = new \ReflectionClass($classname);
            } catch (ReflectionException) {
                continue;
            }

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
                foreach ($reflectionMethod->getAttributes() as $reflectionAttribute) {
                    if (ContentElement::class === $reflectionAttribute->getName()) {
                        $typoscript[] = $this->getPluginDefinition($reflectionAttribute->newInstance(), $reflectionMethod, $reflectionClass->getName());

                        break;
                    }
                }
            }
        }

        return $typoscript;
    }

    private function getPluginDefinition(ContentElement $contentElement, \ReflectionMethod $reflectionMethod, string $classname): string
    {
        $pluginSignature = $contentElement->getName();
        if (!$pluginSignature) {
            $pluginSignature = $this->autodetectNameFromMethodArguments($reflectionMethod);
        }

        $cached = $contentElement->isCached();
        $controller = $classname.'::'.$reflectionMethod->getName();
        $customCacheEnable = $contentElement->usesCustomCache();
        $customCacheLifetime = $contentElement->getCustomCacheLifetime();
        $customCacheTagList = implode(',', $contentElement->getCustomCacheTags());

        $pluginType = 'USER'.($cached ? '' : '_INT');
        $userFunc = Renderer::class.'->handle';

        $pluginContent = /* @lang TYPO3_TypoScript */ <<<EOTS
# Setting $pluginSignature content element
tt_content.$pluginSignature = $pluginType 
tt_content.$pluginSignature {
    userFunc = $userFunc
    controller = $controller
    custom_cache {
        enabled = $customCacheEnable
        lifetime = $customCacheLifetime
        tags = $customCacheTagList
    }
}
EOTS;

        return \trim($pluginContent);
    }

    private function autodetectNameFromMethodArguments(\ReflectionMethod $reflectionMethod): ?string
    {
        // loop through all parameters of the annotated method
        foreach ($reflectionMethod->getParameters() as $parameter) {
            // skip strings, numbers, boolean and arrays
            if ($parameter->getType() && $parameter->getType()->isBuiltin()) {
                continue;
            }

            // get the parameter's class name
            /** @noinspection PhpUnhandledExceptionInspection */
            $parameterReflectionClass = new \ReflectionClass($parameter->getType()->getName());

            // check if the class extends the TYPO3 extbase entity and has our static 'getRecordType' to read its CType
            if ($parameterReflectionClass->isSubclassOf(AbstractEntity::class) && $parameterReflectionClass->hasMethod('getRecordType')) {
                /** @noinspection PhpUndefinedMethodInspection */
                return ($parameterReflectionClass->getName())::getRecordType();
            }
        }

        return null;
    }

    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        if (null === $this->configCacheFactory) {
            $this->configCacheFactory = new ConfigCacheFactory($this->debug);
        }

        return $this->configCacheFactory;
    }
}
