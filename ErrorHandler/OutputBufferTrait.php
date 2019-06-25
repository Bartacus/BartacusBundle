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

trait OutputBufferTrait
{
    /**
     * Clean the output buffer opened by the TwigBundle and the BartacusTwigBundle
     * for Fatal Errors as they won't be cleaned by themselves and result in an
     * empty 500 error page instead of printing the exception stack trace.
     */
    public function fixOutputBuffer(\Throwable $exception): void
    {
        // check for exceptions or fatal errors
        if (!$exception instanceof \Exception) {
            // clean all output buffers down to the CompressionUtility as this should be the latest one
            while (\ob_get_level() > 0) {
                $status = \ob_get_status();

                if ('TYPO3\\CMS\\Frontend\\Utility\\CompressionUtility::compressionOutputHandler' === (string) $status['name']) {
                    break;
                }

                \ob_end_clean();
            }
        }
    }
}
