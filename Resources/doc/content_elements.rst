.. _content:

================
Content Elements
================

With Bartacus you are able to dispatch content elements to Symfony controller
actions. This creates a harmony with the ability to dispatch routes directly to
Symfony and not to TYPO3.

Usage
=====

To dispatch content elements to Symfony, you have to define a ``@ContentElement``
annotation on your action method. Same like a Symfony ``@Route`` annotation.

Content elements can live under `Action` or `Controller` namespace within your
bundle.

The data which is usually retrieved via ``$this->cObj->data`` in old pi_base
plugin is now injected into the ``$data`` parameter of the method if it exists.

.. code-block:: php

    <?php
    // src/AppBundle/Controller/ContentController.php

    declare(strict_types=1);

    namespace AppBundle\Controller;

    use Bartacus\Bundle\BartacusBundle\Annotation\ContentElement;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;

    class ContentController extends AbstractController
    {
        /**
         * @ContentElement("content_text")
         */
        public function textAction(array $data): Response
        {
            // ..

            return $this->render('content/text.html.twig', [
                // ..
            ]);
        }
    }

To have the plugin not cached, add the ``cached=false`` attribute into the
annotation, e.g: ``@ContentElement("form_contact", cached=false)``

Configuration of the TCA for inserting the content element in the backend and
available fields MUST be done in ``Configuration/TCA`` and
``Configuration/TCA/Overrides`` as usual.
(TODO: Use models and annotations too)

.. note::

    Bartacus mocks the Symfony http foundation kernel requests, which means you have access to the ``Request`` instance as a sub request as seen above and must return a ``Response`` instance, but only a subset of the usual kernel events are dispatched.

Redirect responses
------------------

You are able to use redirect responses in a content element action too.
Bartacus detects the ``RedirectResponse`` instance and sends the redirect
headers, terminates the kernel and exits. No further TYPO3 code is executed.

404 responses
-------------

You have two options how you can handle 404 responses. Either you throw a not
found exception with ``throw $this->createNotFoundException();`` and TYPO3 will
take care of it with the configure page not found handler.

Or if you want to show a special content if nothing is found, but still need a
404 status code for SEO reasons, render your special content and return a normal
response object with the status code set to 404.
