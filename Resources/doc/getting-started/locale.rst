===============
Locale handling
===============

The locale handling with Bartacus differs a little bit from the default you are
used to in Symfony.

Frontend
========

The locale on the request is set from the TSFE locale context on the domain you
are on. It uses the ``sys_language_isocode`` which is automatically derived by
TYPO3 from your ``sys_language`` settings in the backend. Optionally you can
override this field in your TypoScript config.

Content elements
----------------

As a result, using the translator in a content element gets always the correct
language the frontend expects.

Symfony routes
--------------

You must set the correct TSFE ``sys_language_uid`` by always adding the ``?L=x``
query parameter to your routes! Adding the ``L`` parameter is important to
initialize the correct TSFE instance, e.g. for getting the correct translated
database records.

Additionally it's still possible to encode ``{_locale}`` in your route, which
overwrites the locale from the TSFE for the translator component.

Backend / TYPO3 CLI
===================

Since there is no TSFE or symfony request handled, the translator uses the
default locale, configured in your ``config.yml``.
