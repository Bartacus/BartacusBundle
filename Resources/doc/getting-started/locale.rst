===============
Locale handling
===============

The locale handling with Bartacus differs a little bit from the default you are
used to in Symfony.

Frontend
========

The locale on the request is set from the site config locale context on the domain you are on. It derives from the ``locale`` in of your site configuration.

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
overwrites the locale from the site config for the translator component.

Backend / TYPO3 CLI
===================

Since there is no TSFE or symfony request handled, the translator uses the
default locale, configured in your ``config/packages/translation.yml``.
