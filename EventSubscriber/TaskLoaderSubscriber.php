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
use Bartacus\Bundle\BartacusBundle\Scheduler\TaskGenerator;
use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;
use Bartacus\Bundle\BartacusBundle\UpgradeWizard\TaskProxyUpdateWizard;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;

final class TaskLoaderSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct()
    {
        $this->optionsResolver = new OptionsResolver();

        $this->configureDefaults($this->optionsResolver);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::ADDITIONAL_CONFIGURATION => [['loadTasks', 8]],
        ];
    }

    public function loadTasks(Event $event): void
    {
        if (TYPO3_MODE === 'BE') {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][TaskProxyUpdateWizard::IDENTIFIER] = TaskProxyUpdateWizard::class;

            $this->taskGenerator()->registerAutoloader();
            $mapping = $this->taskGenerator()->generateAll();

            /** @var string|TaskInterface $taskClass */
            foreach ($mapping as $taskClass => $proxyClassName) {
                $options = $this->optionsResolver->resolve($taskClass::getConfiguration());

                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$proxyClassName] = $options;
            }
        }
    }

    private function configureDefaults(OptionsResolver $options): void
    {
        $options->setDefault('extension', 'app');
        $options->setAllowedTypes('extension', 'string');

        $options->setRequired('title');
        $options->setAllowedTypes('title', 'string');

        $options->setRequired('description');
        $options->setAllowedTypes('description', 'string');

        $options->setDefined('additionalFields');
        $options->setAllowedTypes('additionalFields', 'string');
        $options->setAllowedValues('additionalFields', static function (string $value) {
            return \class_exists($value) && $value instanceof AdditionalFieldProviderInterface;
        });
    }

    private function taskGenerator(): TaskGenerator
    {
        return $this->container->get(__METHOD__);
    }
}
