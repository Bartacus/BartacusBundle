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
    |   +-- AcmeContent.php
    +-- Resources
    |   +-- views
    |       +-- Text
    |           +-- text.html.twig
    +-- plugins
        +-- class.tx_content_text.php

As you can see, the important class in your extension is the ``AcmeContent.php``,
which transforms your extension into a Symfony bundle. Obviously it uses the
similar naming convention to Symfony, so take a vendor name and your extension
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

Now the content element plugin. Old style, but simple.

.. code-block:: php

    <?php

    use Bartacus\Bundle\BartacusBundle\Typo3\Plugin;

    class tx_content_text extends Plugin
    {
        /**
         * Execute the plugin, e.g. retrieve data, render it's content..
         *
         * @return string The content that is displayed on the website
         */
        protected function execute()
        {
            return $this->render('AcmeContent:Text:text.html.twig', [
                'data' => $this->cObj->data,
            ]);
        }
    }

Accessing the container
=======================

The ``Plugin`` class from Bartacus provides some convenient methods to access
the container. Alternative the container is accessible via ``$this->container``.

.. code-block:: php

    $service = $this->get('service_id');
    // or
    $service = $this->container->get('service_id');
