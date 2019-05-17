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

use TYPO3\CMS\Core\Error\ProductionExceptionHandler;

/**
 * The custom TYPO3 debug exception handler is used when APP_DEBUG is false and TYPO3->SYS->display_errors is 1.
 */
class Typo3ProductionExceptionHandler extends ProductionExceptionHandler
{
    use OutputBufferTrait;

    /**
     * @throws \Exception
     */
    public function handleException(\Throwable $exception)
    {
        $this->fixOutputBuffer($exception);

        parent::handleException($exception);
    }
}
