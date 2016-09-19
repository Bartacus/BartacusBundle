<?php declare(strict_types=1);

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

namespace Bartacus\Bundle\BartacusBundle\ContentElement;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * @DI\Service("bartacus.content_element.config_loader", public=false)
 */
class ContentElementConfigLoader
{
    /**
     * @var RenderDefinitionCollection|null
     */
    protected $collection;

    /**
     * @var string[]
     */
    protected $bundles = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $typoScriptLoaded = false;

    /**
     * @var ConfigCacheFactoryInterface
     */
    private $configCacheFactory;

    /**
     * @DI\InjectParams(params={
     *      "container" = @DI\Inject("service_container"),
     *      "bundles" = @DI\Inject("%jms_di_extra.bundles%"),
     *      "cacheDir" = @DI\Inject("%kernel.cache_dir%"),
     *      "debug" = @DI\Inject("%kernel.debug%")
     * })
     */
    public function __construct(ContainerInterface $container, array $bundles = [], string $cacheDir = null, bool $debug = false)
    {
        $this->container = $container;
        $this->bundles = $bundles;
        $this->setOptions([
            'cache_dir' => $cacheDir,
            'debug' => $debug,
        ]);
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
    public function setOptions(array $options)
    {
        $this->options = [
            'cache_dir' => null,
            'debug' => false,
        ];

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = [];
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf(
                'The Content Element loader does not support the following options: "%s".',
                implode('", "', $invalid)
            ));
        }
    }

    public function load()
    {
        if (true === $this->typoScriptLoaded) {
            return;
        }

        if (null === $this->options['cache_dir']) {
            $renderDefinitions = $this->getRenderDefinitionCollection();

            foreach ($renderDefinitions as $renderDefinition) {
                ExtensionManagementUtility::addTypoScript(
                    'Bartacus',
                    'setup',
                    $this->renderPluginContent($renderDefinition),
                    'defaultContentRendering'
                );
            }

            $this->typoScriptLoaded = true;

            return;
        }

        $cache = $this->getConfigCacheFactory()
            ->cache($this->options['cache_dir'].'/content_elements.ts',
                function (ConfigCacheInterface $cache) {
                    $renderDefinitions = $this->getRenderDefinitionCollection();

                    $typoScripts = [];
                    foreach ($renderDefinitions as $renderDefinition) {
                        $typoScripts[] = $this->renderPluginContent($renderDefinition);
                    }

                    $output = implode("\n\n", $typoScripts);
                    $cache->write($output, $renderDefinitions->getResources());
                }
            )
        ;

        ExtensionManagementUtility::addTypoScript(
            'Bartacus',
            'setup',
            file_get_contents($cache->getPath()),
            'defaultContentRendering'
        );
    }

    /**
     * @return RenderDefinitionCollection
     *
     * @throws \Exception
     */
    private function getRenderDefinitionCollection(): RenderDefinitionCollection
    {
        if (null === $this->collection) {
            $this->collection = $this->container
                ->get('bartacus.content_element.loader')
                ->load($this->bundles, 'annotation')
            ;
        }

        return $this->collection;
    }

    /**
     * @param RenderDefinition $renderDefinition
     *
     * @return string
     */
    private function renderPluginContent(RenderDefinition $renderDefinition): string
    {
        $pluginSignature = $renderDefinition->getName();
        $cached = $renderDefinition->isCached();
        $controller = $renderDefinition->getController();

        $pluginContent = trim('
# Setting '.$pluginSignature.' content element TypoScript
tt_content.'.$pluginSignature.' = USER'.($cached ? '' : '_INT').'
tt_content.'.$pluginSignature.' {
    userFunc = '.Renderer::class.'->handle
    controller = '.$controller.'
}');

        return $pluginContent;
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
