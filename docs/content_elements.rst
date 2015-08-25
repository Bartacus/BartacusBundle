.. _content:

================
Content Elements
================

With Bartacus you are able to dispatch content elements to Symfony controller
actions. This creates a harmony with the future ability to dispatch routes
directly to Symfony and not to TYPO3.

Configuration
=============

To dispatch content elements to Symfony, Bartacus makes a trick with a special
plugin routing style. To make this work you have to activate the Symfony
routing, although the ``routing.yml`` can be empty. Your content elements are
configured in the ``plugins.yml``. Add this to your main ``config.yml``:

.. code-block:: yaml

    # fileadmin/app/config/config.yml

    framework:
        router:
            resource: "%kernel.root_dir%/config/routing.yml"
            strict_requirements: ~

    bartacus:
        plugins:
            resource: "%kernel.root_dir%/config/plugins.yml"
            strict_requirements: ~

An example contact form looks like the following:

.. code-block:: yaml

    # fileadmin/app/config/plugins.yml

    contact_form:
        path: /contact/form
        defaults: { _controller: AcmeContact:Contact:send, _cached: false }

You have to take care about the naming convention of the ``path`` part. The
first part is always the extension key and the second part the plugin name.
This naming is a MUST. Otherwise it won't work. This would be the equivalent to
a ``tx_contact_form`` plugin class of pi_base plugins.

The ``_cached`` parameter is optional and if not given, it defaults to true.
If false, the content element is created as ``USER_INT`` and will not be cached.

You can also import the plugin configuration with the usage of a prefix, which
simplifies the path a little:

.. code-block:: yaml

    # fileadmin/app/config/plugins.yml

    contact:
        resource: "@AcmeContact/Resources/config/plugins.yml"
        prefix: /contact


.. code-block:: yaml

    # typo3conf/ext/contact/Resources/config/plugins.yml

    contact_form:
        path: /form
        defaults: { _controller: AcmeContact:Contact:send, _cached: false }

Configuration of the TCA for inserting the plugin in the backend and available
fields MUST be done in ``Configuration/TCA`` and ``Configuration/TCA/Overrides``
as usual.

Usage
=====

The code for the content element is simple like a Symfony controller.

.. code-block:: php

    <?php
    // typo3conf/ext/contact/Classes/Controller/ContactController.php

    namespace Acme\Extensions\Contact\Controller;

    use Acme\Extensions\Contact\Form\Model\Contact;
    use Acme\Extensions\Contact\Form\Type\ContactType;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Request;

    class ContactController extends Controller
    {
        public function sendAction(Request $request, $data)
        {
            $form = $this->createForm(new ContactType(), new Contact());

            $form->handleRequest($request);
            if ($form->isValid()) {
                /** @var Contact $contact */
                $contact = $form->getData();

                $emailTo = $this->getParameter('contact.email');
                $message = \Swift_Message::newInstance()
                    ->setSubject('New message: '.$contact->getSubject())
                    ->setSender($contact->getEmail())
                    ->setReplyTo($contact->getEmail())
                    ->setFrom(is_array($emailTo) ? $emailTo[0] : $emailTo)
                    ->setTo($emailTo)
                    ->setBody(
                        $this->renderView(
                            'AcmeContact::email.txt.twig',
                            ['contact' => $contact]
                        ),
                        'text/plain'
                    )
                ;

                $this->get('mailer')->send($message);

                return $this->render('AcmeContact::thanks.html.twig');
            }

            return $this->render(
                'AcmeContact::show.html.twig',
                [
                    'header' => $data['header'],
                    'form' => $form->createView(),
                ]
            );
        }
    }

The data which is usually retrieved via ``$this->cObj->data`` in old pi_base
plugin is now injected into the ``$data`` parameter of the method if it exists.

.. note::

    Bartacus mocks the Symfony http foundation kernel requests, which means you
    have access to the ``Request`` instance as a sub request as seen above and
    must return a ``Response`` instance, but none of the usual kernel events are
    dispatched.
