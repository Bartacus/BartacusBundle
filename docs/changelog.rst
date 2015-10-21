=========
Changelog
=========

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
