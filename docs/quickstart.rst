==========
Quickstart
==========

This page provides a quick introduction to Bartacus and introductory examples.
If you have not already installed Bartacus, head over to the :ref:`installation`
page.

Extension structure
===================

Below you see a basic extension structure for Bartacus with one content element.
Typical TYPO3 extension files are not shown.

.. code-block:: text

    typo3conf/ext/content
    +-- Classes
    |   +-- Controller
    |   |   +-- TextController.php
    |   +-- AcmeContent.php
    +-- Resources
        +-- views
            +-- Text
                +-- text.html.twig

As you can see, the important class in your extension is the ``AcmeContent.php``,
which transforms your extension into a Symfony bundle. Obviously it uses
similar naming convention as Symfony, so take a vendor name and your extension
name and camel case it together. Don't forget to add the ``AcmeContent`` class
to your ``AppKernel``.

.. code-block:: php

    <?php

    namespace Acme\Extensions\Content;

    use Bartacus\Bundle\BartacusBundle\Typo3\Typo3Extension;

    /**
     * Transforms this extension to a "symfony bundle"
     */
    class AcmeContent extends Typo3Extension
    {

    }

Now the content element controller:

.. code-block:: php

    <?php
    // typo3conf/ext/acme/Classes/Controller/TextController.php

    namespace Acme\Extensions\Contact\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class TextController extends Controller
    {
        public function showAction($data)
        {
            return $this->render('AcmeContent:Text:text.html.twig', [
                'data' => $data,
            ]);
        }
    }

To get the content element controller registered in the frontend, add the
following to your global ``plugins.yml``:

.. code-block:: yaml

    # fileadmin/app/config/plugins.yml

    content_text:
        path: /content/text
        defaults: { _controller: AcmeContent:Text:show }

For the backend, add the TCA stuff as usual. More information about content
elements as controllers are found in the :ref:`content` section.

Accessing the container
=======================

The ``Controller`` class from Symfony provides some convenient methods to access
the container. Alternative the container is accessible via ``$this->container``.

.. code-block:: php

    $service = $this->get('service_id');
    // or
    $service = $this->container->get('service_id');
