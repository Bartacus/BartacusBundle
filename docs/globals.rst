=========================
TYPO3 bridge and services
=========================

The common TYPO3 classes are available in the service container for you:

The ``TYPO3\CMS\Core\Cache\CacheManager`` is available as
``typo3.cache.cache_manager`` and the commom caches can be retrieved via
``typo3.cache.cache_hash``, ``typo3.cache.cache_pages``,
``typo3.cache.cache_pagesection`` and ``typo3.cache.cache_rootline``.

The ``TSFE`` is available as ``typo3.frontend_controller``, the ``sys_page`` on
the TSFE as ``typo3.page_repository`` and the ``cObj`` on the TSFE as
``typo3.content_object_renderer`` service.

The ``TYPO3_DB`` is available as ``typo3.db`` service.

The ``BE_USER`` is available as ``typo3.backend_user`` service. This service
may be ``null`` if no backend user is logged in.

The ``TYPO3\CMS\Core\Resource\FileRepository`` for the FAL is available as
``typo3.file_repository``.

The ``TYPO3\CMS\Frontend\Page\CacheHashCalculator`` is available as

Globals and ``makeInstace``
===========================

Although you have a common set of services available above, sometimes you need
access to some of the other TYPO3 globals or retrieve other TYPO3 classes with
``GeneralUtility::makeInstance()``. This will clutter your code and is really
bad as it makes your services not testable.

Instead you can create services from TYPO3 globals with the factory pattern:

.. configuration-block::

    .. code-block:: yaml

        services:
            app.typo3.frontend_user:
                class: TYPO3\CMS\Core\Authentication\FrontendUserAuthentication
                factory: ["@typo3", getGlobal]
                arguments:
                    - FE_USER

    .. code-block:: xml

        <services>
            <service id="app.typo3.frontend_user" class="TYPO3\CMS\Core\Authentication\FrontendUserAuthentication">
                <factory service="typo3" method="getGlobal"/>
                <argument>FE_USER</argument>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\ExpressionLanguage\Expression;

        $definition = new Definition(
            'TYPO3\\CMS\\Core\\Authentication\\FrontendUserAuthentication',
            ['FE_USER']
        ]);
        $definition->setFactory([
            new Reference('typo3'),
            'getGlobal'
        ]);
        $container->setDefinition('app.typo3.frontend_user', $definition);

The same it possible with classes from ``GeneralUtility::makeInstance()``, but
the must be set shared to false, so ``makeInstance()`` is still in control
whether you get the same instance or a new one every time you inject the
service.

.. configuration-block::

    .. code-block:: yaml

        services:
            app.typo3.template_service:
                class: TYPO3\CMS\Core\TypoScript\TemplateService
                shared: false
                factory: ["@typo3", makeInstance]
                arguments:
                    - "TYPO3\\CMS\\Core\\TypoScript\\TemplateService"

    .. code-block:: xml

        <services>
            <service id="app.typo3.template_service" class="TYPO3\CMS\Core\TypoScript\TemplateService" shared="false">
                <factory service="typo3" method="makeInstance"/>
                <argument>TYPO3\CMS\Core\TypoScript\TemplateService</argument>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\ExpressionLanguage\Expression;

        $definition = new Definition(
            'TYPO3\\CMS\\Core\\TypoScript\\TemplateService',
            ['TYPO3\\CMS\\Core\\TypoScript\\TemplateService']
        ]);
        $definition->setShared(false);
        $definition->setFactory([
            new Reference('typo3'),
            'makeInstance'
        ]);
        $container->setDefinition('app.typo3.template_service', $definition);

Other caches as service
=======================

If you have defined your own cache in your extension, make it available to the
service container to. It's the same as getting a global from TYPO3, but instead
you are using the cache manager as a factory.

The configured cache in this example is ``acme_geocoding``:

.. configuration-block::

    .. code-block:: yaml

        services:
            app.cache.acme_geocoding:
                class: TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
                factory: ["@typo3.cache.cache_manager", getCache]
                arguments:
                    - acme_geocoding

    .. code-block:: xml

        <services>
            <service id="app.cache.acme_geocoding" class="TYPO3\CMS\Core\Cache\Frontend\FrontendInterface">
                <factory service="typo3.cache.cache_manager" method="getCache"/>
                <argument>acme_geocoding</argument>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\ExpressionLanguage\Expression;

        $definition = new Definition(
            'TYPO3\\CMS\\Core\\Cache\\Frontend\\FrontendInterface',
            ['acme_geocoding']
        ]);
        $definition->setFactory([
            new Reference('typo3.cache.cache_manager'),
            'getCache'
        ]);
        $container->setDefinition('app.cache.acme_geocoding', $definition);
