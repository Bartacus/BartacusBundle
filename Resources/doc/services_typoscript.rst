======================
Services in TypoScript
======================

Symfony has an excellent service container with dependency injection. But in a
while you have to configure some user function in TypoScript which are
expecting the class name. This would prevent the use of proper DI.

Fortunately Bartacus integrates the service container into TYPO3 so your access
to configured classes in TypoScript is automatically transformed into a (lazy)
service container load.

TypoScript user functions
=========================

Define your class as service with the tag ``bartacus.typoscript``. For more
information about the service container see the
`Symfony Service Container Documentation <http://symfony.com/doc/current/book/service_container.html>`_.


.. code-block:: php
    <?php

    declare(strict_types=1);

    namespace AppBundle\TypoScript;

    use JMS\DiExtraBundle\Annotation as DI;

    /**
     * @DI\Service()
     * @DI\Tag("bartacus.typoscript")
     */
    class PageTitle
    {
        // ...
    }

Now you can use your class in your TypoScript user funcs and the service will
be initialized.

.. code-block:: text

    site.config.titleTagFunction = AppBundle\TypoScript\PageTitle->getPageTitle

Normally you would get passed the calling ``ContentObjectRender`` passed into a
public property ``cObj``. When using services for user functions you get passed
the calling content object as third parameter to the method.
