<?php

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

declare(strict_types=1);

namespace Bartacus\Bundle\BartacusBundle\Config;

use Bartacus\Bundle\BartacusBundle\ContentElement\ContentElementConfigLoader;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Delegating central config loader called on various places within TYPO3
 * to load and configure specific parts of the system.
 *
 * @DI\Service("bartacus.config_loader")
 */
class ConfigLoader
{
    /**
     * @var ContentElementConfigLoader
     */
    protected $contentElement;

    /**
     * @DI\InjectParams(params={
     *      "contentElement" = @DI\Inject("bartacus.content_element.config_loader")
     * })
     *
     * @param ContentElementConfigLoader $contentElement
     */
    public function __construct(ContentElementConfigLoader $contentElement)
    {
        $this->contentElement = $contentElement;
    }

    public function loadFromAdditionalConfiguration(): void
    {
        $this->contentElement->load();
    }
}
