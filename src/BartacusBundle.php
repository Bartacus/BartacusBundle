<?php

/*
 * This file is part of the Bartacus project.
 *
 * Copyright (c) 2015 Patrik Karisch, pixelart GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Bartacus\Bundle\BartacusBundle;

use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\NopCompilerPass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3ConfVarsCompilerPass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3UserFuncCompilerPass;
use Bartacus\Bundle\BartacusBundle\DependencyInjection\Compiler\Typo3UserObjCompilerPass;
use Bartacus\Bundle\BartacusBundle\Typo3\UserObjAndFuncManager;
use Cocur\Slugify\Slugify;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Routing\Route;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * The bundle!
 *
 * @author Patrik Karisch <p.karisch@pixelart.at>
 */
class BartacusBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController'] = [
            'className' => 'Bartacus\\Bundle\\BartacusBundle\\Typo3\\Xclass\\TypoScriptFrontendController'
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'] = [
            'className' => 'Bartacus\\Bundle\\BartacusBundle\\Typo3\\Xclass\\ContentObjectRenderer'
        ];

        if (
            isset($_SERVER['REQUEST_URI'])
            && ($dispatchUris = $this->container->getParameter('bartacus.dispatch_uris'))
        ) {
            foreach ($dispatchUris as $dispatchUri) {
                if (0 === strpos($_SERVER['REQUEST_URI'], $dispatchUri)) {
                    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['bartacus_app'] = str_replace(
                        realpath(PATH_site).'/',
                        '',
                        __DIR__.'/app.php'
                    );

                    $_GET['eID'] = 'bartacus_app';
                }
            }
        }

        /** @var UserObjAndFuncManager $userObjAndFuncManager */
        $userObjAndFuncManager = $this->container->get(
            'typo3.user_obj_and_func_manager'
        );

        $userObjAndFuncManager->generateUserObjs();
        $userObjAndFuncManager->generateUserFuncs();

        $this->registerPlugins();

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'bartacus.cache_clearer->clear';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['symfony'] = array(
            str_replace(
                realpath(PATH_site).'/',
                '',
                __DIR__.'/console.php'
            ),
            '_CLI_symfony',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new NopCompilerPass());
        $container->addCompilerPass(new Typo3UserObjCompilerPass());
        $container->addCompilerPass(new Typo3UserFuncCompilerPass());
    }

    private function registerPlugins()
    {
        /** @var Router $router */
        $router = $this->container->get('router.plugins');

        $wizards = [];
        $newWizardHeaders = [];

        /** @var Route $route */
        foreach ($router->getRouteCollection()->getIterator() as $route) {
            $path = $route->getPath();
            $path = ltrim($path, '/');
            list($extensionName, $pluginName) = explode('/', $path);

            $cached = $route->getDefault('_cached');
            $cached = null === $cached ? true : $cached;

            $pluginSignature = strtolower($extensionName . '_' . $pluginName);

            if ($route->hasDefault('_wizard')) {
                $wizard = $route->getDefault('_wizard');
                $header = 'common';

                if (!empty($wizard['header'])) {
                    /** @var Slugify $slugify */
                    $slugify = $this->container->get('slugify');

                    $header = $slugify->slugify($wizard['header']);
                    $newWizardHeaders[$header] = $wizard['header'];
                }

                $wizards[$header][$pluginSignature] = [
                    'title' => $wizard['title'],
                    'description' => $wizard['description'],
                    'icon' => '../typo3conf/ext/'.$extensionName.'/Resources/icons/wizard/'.$wizard['icon'],
                ];
            }

            $pluginContent = trim('
plugin.tx_'.$pluginSignature.' = USER'.($cached ? '' : '_INT').'
plugin.tx_'.$pluginSignature.' {
	userFunc = bartacus.plugin_dispatcher->handle
	extensionName = '.$extensionName.'
	pluginName = '.$pluginName.'
}');

            ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting '.$pluginSignature.' plugin TypoScript
'.$pluginContent);

            $addLine = trim('
tt_content.'.$pluginSignature.' = COA
tt_content.'.$pluginSignature.' {
	20 = < plugin.tx_'.$pluginSignature.'
}
');

            ExtensionManagementUtility::addTypoScript($extensionName, 'setup', '
# Setting '.$pluginSignature.' plugin TypoScript
'.$addLine.'
', 'defaultContentRendering');
        }

        $this->registerWizards($wizards, $newWizardHeaders);
    }

    /**
     * @param array $wizards [header => [plugin => wizard]
     * @param array $newWizardHeaders [header => name]
     */
    private function registerWizards(array $wizards, array $newWizardHeaders)
    {
        $tsConfig = '';

        foreach ($newWizardHeaders as $header => $name) {
            $tsConfig .= "mod.wizards.newContentElement.wizardItems.{$header}.header = {$name}\n";
        }

        foreach ($wizards as $header => $newWizards) {
            foreach ($newWizards as $plugin => $wizard) {
                $tsConfig .= "mod.wizards.newContentElement.wizardItems.{$header}.elements.{$plugin} {
    title = {$wizard['title']}
    description = {$wizard['description']}
    icon = {$wizard['icon']}
    tt_content_defValues {
		CType = {$plugin}
	}
}
mod.wizards.newContentElement.wizardItems.{$header}.show := addToList({$plugin})\n";
            }
        }

        ExtensionManagementUtility::addPageTSConfig($tsConfig);
    }
}
