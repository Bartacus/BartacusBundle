============
Translations
============

`Translation`_ handling works the same as you are used to in Symfony projects. Although there are some minor differences listed below.

Locale handling
===============

The biggest difference is how the locale for the request and the translator is retrieved.

Content Elements
----------------

When rendering pages, the locale is retrieved from your `configured site language`_ ``locale`` setting and set as the request and translator locale.

The ``base`` setting is trimmed and injected as ``_locale`` attribute into the request. This assumes all your sites must have the locale configured as base path only (e.g. ``/en/``). This is a restriction currently required to work if you use a pretty locale with country code in your base path (e.g. ``/de-at/``).

Example with language locale only
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml

    # config/sites/main/config.yml
    rootPageId: 1
    base: /

    languages:
      -
        title: 'Deutsch'
        enabled: true
        languageId: 0
        base: /de/
        typo3Language: de
        locale: de_DE.UTF-8
        iso-639-1: de
        navigationTitle: 'Deutsch'
        hreflang: de
        direction: ltr
        flag: de
        fallbackType: strict

This will set the following:

    * Request and translator locale will be set to ``de_DE``
    * The ``_locale`` attribute in the request will be set to ``de``

Example with language and country locale
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: yaml

    # config/sites/main/config.yml
    rootPageId: 1
    base: /

    languages:
      -
        title: 'Deutsch (Deutschland)'
        enabled: true
        languageId: 0
        base: /de-de/
        typo3Language: de
        locale: de_DE.UTF-8
        iso-639-1: de
        navigationTitle: 'Deutsch (Deutschland)'
        hreflang: de
        direction: ltr
        flag: de
        fallbackType: strict

This will set the following:

    * Request and translator locale will be set to ``de_DE``
    * The ``_locale`` attribute in the request will be set to ``de-de``

Symfony Routes
--------------

There is a multi step process to set the locale:

    * If there is a ``_locale`` attribute in the request, this one is used, harmonized and set for the request and translator locale
    * If the Symfony route matches a configured TYPO3 site too then the request and translator locale is overridden with the locale from the resolved TYPO3 site.
    * If there is no ``_locale`` attribute in the request, but the Symfony route matches a configured TYPO3 site it is handled like the content elements above.

Harmonization: You can still use a clean locale like ``de-at`` and it is normalized to ``de_AT`` for the request and translator locale internally.

.. hint::

    Use the ``{_locale}`` placeholder in the beginning of your routes and it will match the configured TYPO3 sites and the ``site`` and ``language`` attributes are available in the request too.

    To prefix all routes with ``/{_locale}`` modify your routes like the following:

    .. code-block:: yaml

        # config/routes/annotations.yaml
        controllers:
            resource: ../../src/Controller/
            type: annotation
            prefix: '/{_locale}'

.. _`Translation`: https://symfony.com/doc/current/translation.html
.. _`configured site language`: https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/SiteHandling/AddLanguages.html
