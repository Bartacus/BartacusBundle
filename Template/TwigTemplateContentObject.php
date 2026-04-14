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

use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\TemplateWrapper;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class TwigTemplateContentObject extends AbstractContentObject
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @throws \Throwable
     * @noinspection PhpUnusedParameterInspection
     */
    public function cObjGetSingleExt(string $name, array $conf, $typoScriptKey, ContentObjectRenderer $cObj): string
    {
        return 'TWIGTEMPLATE' === $name
            ? $this->render($conf)
            : '';
    }

    /**
     * Rendering the cObject, TWIGTEMPLATE.
     *
     * Configuration properties:
     * - template string+stdWrap The Twig template file
     * - variable array of cObjects, the keys are the variable names in twig
     *
     * Example:
     * 10 = TWIGTEMPLATE
     * 10.template = layouts/default.html.twig
     * 10.variables {
     *   mylabel = TEXT
     *   mylabel.value = Label from TypoScript coming
     * }
     *
     * @throws \Throwable
     */
    public function render($conf = []): string
    {
        if (!\is_array($conf)) {
            $conf = [];
        }

        $variables = $this->getContentObjectVariables($conf);
        $variables['settings'] = $this->transformSettings($conf);

        $context = $variables + $this->twig->getGlobals();

        $name = $this->getTemplate($conf);
        $template = $this->twig->load($name);

        $this->renderIntoPageRenderer($template, $context);

        return $this->renderBlock($template, 'body', $context);
    }

    private function getTemplate(array $conf): string
    {
        if ((!empty($conf['template']) || !empty($conf['template.']))) {
            return isset($conf['template.'])
                ? $this->getContentObjectRenderer()->stdWrap($conf['template'] ?? '', $conf['template.'])
                : $conf['template'];
        }

        return 'layouts/default.html.twig';
    }

    private function getContentObjectVariables(array $conf): array
    {
        $cObj = $this->getContentObjectRenderer();

        $variables = [];
        $reservedVariables = ['data', 'current', 'settings'];
        $variablesToProcess = (array) ($conf['variables.'] ?? []);

        // accumulate the variables to be process and loop them through cObjGetSingle
        foreach ($variablesToProcess as $variableName => $cObjType) {
            if (\is_array($cObjType)) {
                continue;
            }

            if (\in_array($variableName, $reservedVariables, true)) {
                throw new \InvalidArgumentException('Cannot use reserved name "'.$variableName.'" as variable name in TWIGTEMPLATE.');
            }

            $variables[$variableName] = $cObj->cObjGetSingle($cObjType, $variablesToProcess[$variableName.'.'] ?? []);
        }

        $variables['data'] = $cObj->data;
        $variables['current'] = $cObj->data[$cObj->currentValKey] ?? null;

        return $variables;
    }

    private function transformSettings(array $conf): array
    {
        if (isset($conf['settings.'])) {
            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

            return $typoScriptService->convertTypoScriptArrayToPlainArray($conf['settings.']);
        }

        return [];
    }

    /**
     * @throws \Throwable
     */
    private function renderIntoPageRenderer(TemplateWrapper $template, array $context): void
    {
        $header = $this->renderBlock($template, 'header', $context);
        $footer = $this->renderBlock($template, 'footer', $context);

        if ($header || $footer) {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

            if ($header) {
                $pageRenderer->addHeaderData($header);
            }

            if ($footer) {
                $pageRenderer->addFooterData($footer);
            }
        }
    }

    /**
     * Renders a Twig block with error handling.
     *
     * This avoids getting some leaked buffer when an exception occurs.
     * Twig blocks are not taking care of it as they are not meant to be rendered directly.
     *
     * @throws \Throwable
     */
    private function renderBlock(TemplateWrapper $template, string $block, array $context): string
    {
        $level = \ob_get_level();
        \ob_start();

        try {
            $rendered = $template->renderBlock($block, $context);
            \ob_end_clean();

            return mb_trim($rendered);
        } catch (\RuntimeException $e) {
            while (\ob_get_level() > $level) {
                \ob_end_clean();
            }

            throw $e;
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (RuntimeError $e) {
            // '404 not found' exceptions thrown by the controller are converted to a
            // \Twig_Error_Runtime in the 'renderBlock', but the real exception is still available as the previous one.
            // If thrown by a controller, then the previous exception is typically a ImmediateResponseException which
            // includes the rendered error page.
            while (\ob_get_level() > $level) {
                \ob_end_clean();
            }

            throw $e->getPrevious() instanceof ImmediateResponseException ? $e->getPrevious() : $e;
        }
    }
}
