============
Translations
============

String translations are possible with the wonderful
`translator service <http://symfony.com/doc/current/book/translation.html>`_
from Symfony. The locale for the translator is retrieved from your typoscript
configuration, thus depending on the typical TYPO3 ``L`` url param.

Basic Configuration
===================

Simple add the following to your ``fileadmin/app/config/config.yml`` if not
already exist trough the standard edition:

.. code-block:: yaml

    parameters:
        locale: en

    framework:
        default_locale:  "%locale%"
        translator:      { fallbacks: ["%locale%"] }

This will activate the translator service and defines the default locale as
fallback locale

.. caution::

    To get the locale retrieving from TypoScript working Bartacus XCLASSes the
    ``TypoScriptFrontendController`` in a very early phase. If you have an
    extension installed which wants to XCLASS the the same class, the extension
    wins, and this functionality stops working.

Translation files
=================

One restriction applies. Translations files can only be placed into real Symfony
bundles ``<bundle>/Resources/translations`` dir or under the global
``fileadmin/app/Resources/translations`` dir. At the moment it is not possible
to place translations into a extension "bundle".
