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

namespace Bartacus\Bundle\BartacusBundle;

/**
 * Contains all events thrown in the config loader.
 */
final class ConfigEvents
{
    /**
     * The ADDITIONAL_CONFIGURATION event occurs at the very beginning
     * when the AdditionalConfiguration.php is parsed.
     *
     * Useful to modify the $GLOBALS['TYPO3_CONF_VARS'] or load TypoScript.
     *
     * @Event("Symfony\Component\EventDispatcher\Event")
     */
    public const ADDITIONAL_CONFIGURATION = 'bartacus.config.additional_configuration';

    /**
     * The REQUEST_MIDDLEWARES event occurs at when the request middlewares
     * of the app extension are loaded.
     *
     * @Event("Bartacus\Bundle\BartacusBundle\Event\RequestMiddlewaresEvent")
     */
    public const REQUEST_MIDDLEWARES = 'bartacus.config.request_middlewares';

    /**
     * The EXTENSION_TABLES event occurs at when the extension tables file
     * of the app extension are loaded.
     *
     * @Event("Bartacus\Bundle\BartacusBundle\Event\ExtensionTablesLoadEvent")
     */
    public const EXTENSION_TABLES = 'bartacus.config.ext_tables';

    /**
     * The EXTENSION_LOCALCONF event occurs at when the request extension localconf file
     * of the app extension are loaded.
     *
     * @Event("Bartacus\Bundle\BartacusBundle\Event\ExtensionTablesLoadEvent")
     */
    public const EXTENSION_LOCAL_CONF = 'bartacus.config.ext_localconf';

    /**
     * The REQUEST_EXTBASE_PERSISTENCE_CLASSES event occurs at when the request the extbase persistence classes
     * of the app extension are loaded.
     *
     * @deprecated since 3.0.3, will be removed in 4.0.0
     *
     * @Event("Bartacus\Bundle\BartacusBundle\Event\RequestExtbasePersistenceClassesEvent")
     */
    public const REQUEST_EXTBASE_PERSISTENCE_CLASSES = 'bartacus.config.request_extbase_persistence_classes';

    /**
     * The EXTBASE_PERSISTENCE_CLASSES event occurs at when the request the extbase persistence classes
     * of the app extension are loaded.
     *
     * @Event("Bartacus\Bundle\BartacusBundle\Event\ExtbasePersistenceClassesEvent")
     */
    public const EXTBASE_PERSISTENCE_CLASSES = 'bartacus.config.extbase_persistence_classes';
}
