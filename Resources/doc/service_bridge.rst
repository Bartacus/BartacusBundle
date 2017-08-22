=====================
TYPO3 bridge services
=====================

The common TYPO3 classes are available in the service container for you.
The class name is the service id as of Symfony 3.3 tp support the new autowiring
feature.

To see which TYPO3 classes are wireable as service, call

.. code-block:: bash

    php bin/container debug:container --types

Additionally the common caches can be retrieved via the ids
``typo3.cache.cache_hash``, ``typo3.cache.cache_pages``,
``typo3.cache.cache_pagesection`` and ``typo3.cache.cache_rootline``.

Globals and ``makeInstance``
===========================

Although you have a common set of services available above, sometimes you need
access to some of the other TYPO3 globals or retrieve other TYPO3 classes with
``GeneralUtility::makeInstance()``. This will clutter your code and is really
bad as it makes your services not testable.

Instead you can create services from TYPO3 globals with the factory pattern:

.. code-block:: yaml

    services:
        TYPO3\CMS\Lang\LanguageService:
            factory: 'Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge:getGlobal'
            shared: false
            arguments:
                - LANG

The same it possible with classes from ``GeneralUtility::makeInstance()``:

.. code-block:: yaml

    services:
        TYPO3\CMS\Core\TypoScript\TemplateService:
            class: TYPO3\CMS\Core\TypoScript\TemplateService
            shared: false
            factory: 'Bartacus\Bundle\BartacusBundle\Typo3\ServiceBridge:makeInstance'
            arguments:
                - 'TYPO3\CMS\Core\TypoScript\TemplateService'

Always the the service to be not shared!

Other caches as service
=======================

If you have defined your own cache in your extension, make it available to the
service container. It's the same as getting a global from TYPO3, but instead
you are using the cache manager as a factory.

The configured cache in this example is ``acme_geocoding``:

.. code-block:: yaml

    services:
        app.cache.acme_geocoding:
            class: TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
            factory: 'TYPO3\CMS\Core\Cache\CacheManager:getCache'
            arguments:
                - acme_geocoding
