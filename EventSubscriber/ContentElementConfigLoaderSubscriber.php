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

namespace Bartacus\Bundle\BartacusBundle\EventSubscriber;

use Bartacus\Bundle\BartacusBundle\ConfigEvents;
use Bartacus\Bundle\BartacusBundle\ContentElement\Loader\ContentElementConfigLoader;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ContentElementConfigLoaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContentElementConfigLoader
     */
    private $contentElement;

    public function __construct(ContentElementConfigLoader $contentElement)
    {
        $this->contentElement = $contentElement;
    }

    public function loadContentElements(Event $event): void
    {
        $this->contentElement->load();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::ADDITIONAL_CONFIGURATION => [['loadContentElements', 8]],
        ];
    }
}
