======================
Ajax / Symfony Routing
======================

Beside content elements with Symfony you can create whole applications or
ajax request with the usual Symfony full stack framework and routing.

A full Symfony kernel dispatch is registered as TYPO3 eID script and a TSFE
object is initialized for you. So you have access to the usual TYPO3
functionality within the Symfony framework.

Configuration
=============

To not check every request against the Symfony routes you have to configure
route prefixes which should be dispatched. Add the dispatch URIs to your main
``config.yml``. For example:

.. code-block:: yaml

    # fileadmin/app/config/config.yml

    bartacus:
        dispatch_uris:
            - /retailer/
            - /shared/
            - /filter/
            - /event/

So any ``/event/123`` or similar URL will be dispatched by the Symfony kernel.
Any URL which matches a given dispatch URI, but the route is not found generates
a normal 404 error and is not handled back to TYPO3.

Usage
=====

Usage is the same as routing in a full stack Symfony application.
`Read the docs of the Symfony routing <http://symfony.com/doc/current/book/routing.html>`_
to get familiar with it.

One thing you have to take care of: If not passed the TSFE ``sys_language_uid``
is ``0`` and therefore the locale of the translator. You need to pass the ``L``
parameter explicetely to the route by either adding the ``?L=1`` query parameter
as usual or by encoding it in the route itself:

.. code-block:: yaml

    # typo3conf/ext/event/Resources/config/routing.yml

    event_show:
        path: /event/{L}/{id}
        defaults: { _controller: AcmeEvent:Event:show, _format: json }
        requirements:
            _format: json
