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

The data which is usually retrieved via ``$this->cObj->data`` in old pi_base
plugin is now injected into the ``$data`` parameter of the method if it exists.

.. code-block:: php

    <?php declare(strict_types=1);
    // src/AppBundle/Controller/ContentController.php

    namespace AppBundle\Controller;

    use Bartacus\Bundle\BartacusBundle\Annotation\ContentElement;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Response;

    class ContentController extends Controller
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

To have the plugin not cached, add the ``cached=false attribute into the
annotation, e.g: ``@ContentElement("form_contact", cached=false)``

Configuration of the TCA for inserting the content element in the backend and
available fields MUST be done in ``Configuration/TCA`` and
``Configuration/TCA/Overrides`` as usual.
(TODO: Use models and annotations too)

.. note::

    Bartacus mocks the Symfony http foundation kernel requests, which means you
    have access to the ``Request`` instance as a sub request as seen above and
    must return a ``Response`` instance, but none of the usual kernel events are
    dispatched.

Redirect responses
------------------

You are able to use redirect responses in a content element action too.
Bartacus detects the ``RedirectResponse`` instance and sends the redirect
headers, terminates the kernel and exits. No further TYPO3 code is executed.

Reusable bundles
================

If you create a reusable bundle, make sure you register the bundle for the
jms_di_extra config in your prepend extension:

.. code-block:: php

    <?php declare(strict_types=1);

    namespace Acme\Bundle\MyBundle\DependencyInjection;

    use Bartacus\Bundle\BartacusBundle\Exception\DependencyUnsatisfiedException;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
    use Symfony\Component\HttpKernel\DependencyInjection\Extension;

    /**
     * Handle registration with JMSDiExtraBundle and other stuff.
     */
    class BartacusExtension extends Extension implements PrependExtensionInterface
    {
        public function load(array $configs, ContainerBuilder $container)
        {
            // Nothing to load atm.
        }

        public function prepend(ContainerBuilder $container)
        {
            $bundles = $container->getParameter('kernel.bundles');
            if (!isset($bundles['JMSDiExtraBundle'])) {
                throw new DependencyUnsatisfiedException('The JMSDiExtraBundle is not loaded!');
            }

            $container->prependExtensionConfig('jms_di_extra', [
                'locations' => [
                    'bundles' => [
                        'AcmeMyBundle',
                    ],
                ],
            ]);
        }
    }
