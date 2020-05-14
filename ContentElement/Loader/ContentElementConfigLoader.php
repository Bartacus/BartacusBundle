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

use Bartacus\Bundle\BartacusBundle\ContentElement\Definition\RenderDefinition;
use Bartacus\Bundle\BartacusBundle\ContentElement\Definition\RenderDefinitionCollection;
use Bartacus\Bundle\BartacusBundle\ContentElement\Renderer;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

final class ContentElementConfigLoader implements WarmableInterface
{
    /**
     * @var RenderDefinitionCollection|null
     */
    private $collection;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var bool
     */
    private $typoScriptLoaded = false;

    /**
     * @var ConfigCacheFactoryInterface
     */
    private $configCacheFactory;

    /**
     * @var AnnotationContentElementLoader
     */
    private $loader;

    public function __construct(AnnotationContentElementLoader $loader, string $cacheDir = null, bool $debug = false)
    {
        $this->loader = $loader;
        $this->setOptions([
            'cache_dir' => $cacheDir,
            'debug' => $debug,
        ]);
    }

    /**
     * Sets the ConfigCache factory to use.
     *
     * @param ConfigCacheFactoryInterface $configCacheFactory The factory to use
     */
    public function setConfigCacheFactory(ConfigCacheFactoryInterface $configCacheFactory): void
    {
        $this->configCacheFactory = $configCacheFactory;
    }

    /**
     * Sets options.
     *
     * Available options:
     *
     *   * cache_dir:     The cache directory (or null to disable caching)
     *   * debug:         Whether to enable debugging or not (false by default)
     *
     * @param array $options An array of options
     *
     * @throws \InvalidArgumentException When unsupported option is provided
     */
    public function setOptions(array $options): void
    {
        $this->options = [
            'cache_dir' => null,
            'debug' => false,
        ];

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = [];
        foreach ($options as $key => $value) {
            if (\array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(\sprintf('The Content Element loader does not support the following options: "%s".', \implode('", "', $invalid)));
        }
    }

    /**
     * Sets an option.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     *
     * @throws \InvalidArgumentException
     */
    public function setOption(string $key, $value): void
    {
        if (!\array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(\sprintf('The Content Element loader does not support the "%s" option.', $key));
        }

        $this->options[$key] = $value;
    }

    /**
     * Gets an option value.
     *
     * @param string $key The key
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed The value
     */
    public function getOption(string $key)
    {
        if (!\array_key_exists($key, $this->options)) {
            throw new \InvalidArgumentException(\sprintf('The Content Element loader does not support the "%s" option.', $key));
        }

        return $this->options[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir): void
    {
        $currentDir = $this->getOption('cache_dir');

        // force cache generation
        $this->setOption('cache_dir', $cacheDir);
        $this->loadTypoScript();

        $this->setOption('cache_dir', $currentDir);
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

    /**
     * Load the TypoScript code for the content element render definitions
     * itself without adding them to the template.
     *
     * @throws \Exception
     */
    private function loadTypoScript(): string
    {
        if (null === $this->options['cache_dir']) {
            return $this->concatenateTypoScript();
        }

        $cache = $this->getConfigCacheFactory()
            ->cache($this->options['cache_dir'].'/content_elements.typoscript',
                function (ConfigCacheInterface $cache) {
                    $cache->write(
                        $this->concatenateTypoScript(),
                        $this->getRenderDefinitionCollection()->getResources()
                    );
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

        $renderDefinitions = $this->getRenderDefinitionCollection();

        $typoScripts = [];
        foreach ($renderDefinitions as $renderDefinition) {
            $typoScripts[] = $this->renderPluginContent($renderDefinition);
        }

        return $startingConfig.\implode("\n\n", $typoScripts);
    }

    private function getRenderDefinitionCollection(): RenderDefinitionCollection
    {
        if (null === $this->collection) {
            $this->collection = $this->loader->load();
        }

        return $this->collection;
    }

    private function renderPluginContent(RenderDefinition $renderDefinition): string
    {
        $pluginSignature = $renderDefinition->getName();
        $cached = $renderDefinition->isCached();
        $controller = $renderDefinition->getController();
        $pluginType = 'USER'.($cached ? '' : '_INT');
        $userFunc = Renderer::class.'->handle';

        $pluginContent = /* @lang TYPO3_TypoScript */ <<<EOTS
# Setting {$pluginSignature} content element
tt_content.{$pluginSignature} = {$pluginType} 
tt_content.{$pluginSignature} {
    userFunc = {$userFunc}
    controller = {$controller}
}
EOTS;

        return \trim($pluginContent);
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     *
     * @return ConfigCacheFactoryInterface $configCacheFactory
     */
    private function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        if (null === $this->configCacheFactory) {
            $this->configCacheFactory = new ConfigCacheFactory($this->options['debug']);
        }

        return $this->configCacheFactory;
    }
}
