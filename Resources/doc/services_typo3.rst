=================
Services in TYPO3
=================

Symfony has an excellent service container with dependency injection. But in a
while you have to configure some user function in TypoScript, hooks, etc. which
are expecting to initiate the class through ``GeneralUtility::makeInstace()``.
This would prevent the use of proper DI

Fortunately Bartacus integrates the service container into TYPO3 so the call
``GeneralUtility::makeInstace()`` for configured classes automatically
transformed into a (lazy) service container load.

It doesn't matter if you use old snake cased service ids or the new PSR-4
service id naming.

makeInstance calls
==================

Define your class as service with the tag ``bartacus.make_instance``. For more
information about the service container see the
`Symfony Service Container Documentation <http://symfony.com/doc/current/book/service_container.html>`_.


.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace AppBundle\TypoScript;

    class PageTitle
    {
        // ...
    }

.. code-block:: yaml

    // config/services.yml
    services:
        AppBundle\TypoScript\PageTitle:
            tags: [bartacus.make_instance]

Or if you use the autowiring config and have all your TypoScript, Hook, etc. classes in specific subfolders, you can use this shorter autowiring definition instead of declaring and tagging each service.

.. code-block:: yaml

    // services.yml
    services:
        _defaults:
            autowire: true
            autoconfigure: true

        AppBundle\TypoScript\:
            resource: '../src/AppBundle/TypoScript'
            tags: [bartacus.make_instance]

Alternatively, if something implements an interface, you can autoconfigure it with the `_instanceof` and it doesn't matter in which namespace your class lives.

.. code-block:: yaml

    // services.yml
    services:
        _defaults:
            autowire: true
            autoconfigure: true

        _instanceof:
            TYPO3\CMS\Install\Updates\UpgradeWizardInterface:
               tags: [bartacus.make_instance]

Some of the TYPO3 interfaces are already registered for autoconfiguration, so you don't need the above snippets. If you find one, which is not included in the list, head up us a pull request.

* ``TYPO3\CMS\Install\Updates\UpgradeWizardInterface``

Usage
-----

Now you can use your class in your TypoScript user funcs, hooks, etc. and the
service will be initialized and used.

.. code-block:: text

    site.config.titleTagFunction = AppBundle\TypoScript\PageTitle->getPageTitle

Normally you would get passed the calling ``ContentObjectRender`` passed into a
public property ``cObj``. When using services for user functions you get passed
the calling content object as third parameter to the method.

.. code-block:: php

    <?php
    // app/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = LanguageRedirectionService::class.'->redirect';
