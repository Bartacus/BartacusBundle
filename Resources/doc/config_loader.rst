=============
Config Loader
=============

The config loader component is intended for TYPO3 bundle authors. As a project developer you should use the normal extension points to define your configurations.

With the config loader you can extend TYPO3 configuration and so on which is needed by your extension as a requirement.

Usage
=====

The config loader dispatches several events, where you can subscribe to.

The ``bartacus.config.additional_configuration`` event
------------------------------------------------------

The ``bartacus.config.additional_configuration`` event occurs at the very beginning when the ``AdditionalConfiguration.php`` is parsed.

Useful to modify the ``$GLOBALS['TYPO3_CONF_VARS']`` or load TypoScript.

The ``bartacus.config.request_middlewares`` event
-------------------------------------------------

The ``bartacus.config.request_middlewares`` event occurs at when the request middlewares of the app extension are loaded.

Useful to register necessary middlewares on your own, without declaring the bundle as TYPO3 extension too.

.. code-block:: php

    <?php

    namespace Acme\Bundle\AcmeBundle\EventSubscriber;

    use Bartacus\Bundle\BartacusBundle\ConfigEvents;
    use Bartacus\Bundle\BartacusBundle\Event\RequestMiddlewaresEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class RequestMiddlewaresSubscriber implements EventSubscriberInterface
    {
        public function loadMiddlewares(RequestMiddlewaresEvent $event): void
        {
            $middlewares = [
                'frontend' => [
                    // some middleware definitions
                ],
            ];

            $event->addRequestMiddlewares($middlewares);
        }

        public static function getSubscribedEvents(): array
        {
            return [
                ConfigEvents::REQUEST_MIDDLEWARES => 'loadMiddlewares',
            ];
        }
    }

