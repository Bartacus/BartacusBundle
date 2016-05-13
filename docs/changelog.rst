=========
Changelog
=========

0.3.7
=====

* TYPO3 globals are not checked anymore, before accessing them. This prevents
  errors with not yet existing globals.

0.3.6
=====

* Initialize backend user for TSFE on Symfony dispatch too

0.3.5
=====

* Add the ``BE_USER`` global as TYPO3 bridge service

0.3.4
=====

* Add the cache hash calculator as TYPO3 bridge service

0.3.3
=====

* Add full symfony routing/kernel dispatch within TYPO3 eID context and TSFE
  available.
* Handle redirect responses from content element actions.
* Create a bridge session storage to start session if not already started.
* Fix path to console and eID dispatch if deployed in a symlinked environment.
* Access to frozen ``TYPO3_CONF_VARS`` within Symfony container.
* Improve the typo3 bridge with predefined services and better docs.

0.3.2
=====

* Add aliases to user obj hooks to allow references like ``service_id?:alias``.

0.3.1
=====

* Use ``locale_all`` from TypoScript config instead of language. Leads to
  locales with countries.
* Find console command like in normal symfony bundles.

0.3.0
=====

* Clear the Symfony cache from TYPO3 backend.
* The ``Plugin`` class is deprecated. Create Symfony controllers instead.
* Retrieve globals and ``makeInstance`` in service configurations.
* Add routing for content elements to controllers.
* Configure Symfony translator with locale from TypoScript setup.
* Add the content object as third parameter to user functions from services.
* The ``@BartacusBundle/Resources/config/config.yml`` file is removed. Take a
  look at the
  `Bartacus Standard Edition <https://github .com/Bartacus/Bartacus-Standard>`_
  how to fill your own ``config.yml``.
