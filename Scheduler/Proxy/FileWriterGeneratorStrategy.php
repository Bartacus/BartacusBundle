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

namespace Bartacus\Bundle\BartacusBundle\Scheduler\Proxy;

use ProxyManager\Exception\FileNotWritableException;
use ProxyManager\FileLocator\FileLocatorInterface;
use ProxyManager\GeneratorStrategy\GeneratorStrategyInterface;
use Zend\Code\Generator\ClassGenerator;

/**
 * Generator strategy that writes the generated classes to disk while generating them.
 *
 * {@inheritdoc}
 */
class FileWriterGeneratorStrategy implements GeneratorStrategyInterface
{
    /**
     * @var FileLocatorInterface
     */
    protected $fileLocator;

    /**
     * @var callable
     */
    private $emptyErrorHandler;

    public function __construct(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
        $this->emptyErrorHandler = function () {};
    }

    /**
     * Write generated code to disk and return the class code.
     *
     * {@inheritdoc}
     *
     * @throws FileNotWritableException
     */
    public function generate(ClassGenerator $classGenerator): string
    {
        $className = \trim($classGenerator->getNamespaceName(), '\\')
            .'\\'.\trim($classGenerator->getName(), '\\');
        $generatedCode = $classGenerator->generate();
        $fileName = $this->fileLocator->getProxyFileName($className);

        \set_error_handler($this->emptyErrorHandler);

        try {
            $this->writeFile("<?php\n\n".$generatedCode, $fileName);

            return $generatedCode;
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * Writes the source file in such a way that race conditions are avoided when the same file is written
     * multiple times in a short time period.
     *
     * @throws FileNotWritableException
     */
    private function writeFile(string $source, string $location): void
    {
        $tmpFileName = \tempnam($location, 'temporaryProxyManagerFile');
        \unlink($tmpFileName);

        \file_put_contents($tmpFileName, $source);

        if (!\rename($tmpFileName, $location)) {
            \unlink($tmpFileName);

            throw FileNotWritableException::fromInvalidMoveOperation($tmpFileName, $location);
        }
    }
}
