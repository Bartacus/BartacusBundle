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

namespace Bartacus\Bundle\BartacusBundle\ErrorHandler;

use Symfony\Component\Debug\ErrorHandler;

class DebugErrorHandler extends ErrorHandler
{
    /**
     * Overwrite the exception handling as we need to clean the output buffer of
     * the TwigBundle and the BartacusTwigBundle for Fatal Errors as they won't
     * be cleaned by themselves and result in an empty 500 page instead of printing
     * the exception stack trace.
     *
     * @param mixed $exception
     *
     * @throws \ErrorException
     * @throws \Symfony\Component\Debug\Exception\FatalErrorException
     * @throws \Symfony\Component\Debug\Exception\FatalThrowableError
     * @throws \Symfony\Component\Debug\Exception\OutOfMemoryException
     * @throws \Throwable
     */
    public function handleException($exception, array $error = null)
    {
        // check for exceptions or fatal errors
        if (!$exception instanceof \Exception) {
            // fatal error occurred -> clean the output buffer opened by TwigBundle and BartacusTwigBundle
            // clean all output buffers down to the CompressionUtility as this should be the latest one
            while (\ob_get_level() > 0) {
                $status = \ob_get_status();

                if ('TYPO3\\CMS\\Frontend\\Utility\\CompressionUtility::compressionOutputHandler' === (string) $status['name']) {
                    break;
                }

                \ob_end_clean();
            }
        }

        // proceed with the default Symfony exception handling
        return parent::handleException($exception, $error);
    }
}
