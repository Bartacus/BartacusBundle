======================
Services in TypoScript
======================

Symfony has an excellent service container with dependency injection. But in a
while you have to configure some user function in TypoScript or some Hooks,
which are expecting the class name. This would prevent the use of proper DI.

Fortunately Bartacus integrates the service container into TYPO3 so you can
access a service in a TypoScripts ``userFunc`` or hooks.

.. caution::

    To get the user functions in TypoScript working Bartacus XCLASSes the
    ``ContentObjectRender`` in a very early phase. If you have an extension
    installed which wants to XCLASS the the same class, the extension wins, and
    this functionality stops working.

TypoScript ``userFunc``
=======================

Define your class as service with the tag ``typo3.user_func``. This will expose
all public function to be accessible in TypoScript. For more information about
the service container see the
`Symfony Service Container Documentation <http://symfony.com/doc/current/book/service_container.html>`_.

.. configuration-block::

    .. code-block:: yaml

        services:
            helper.frontend:
                class: Acme\Extensions\Content\Helper\FrontendHelper
                tags:
                    -  { name: typo3.user_func }

    .. code-block:: xml

        <services>
            <service id="helper.frontend" class="Acme\Extensions\Content\Helper\FrontendHelper">
                <tag name="typo3.user_func"/>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;

        $definition = new Definition('Acme\Extensions\Content\Helper\FrontendHelper');
        $definition->addTag('typo3.user_func');
        $container->setDefinition('helper.frontend', $definition);

Now you can use your service in a TypoScript ``userFunc`` and consorts:

.. code-block:: text

    site.config.titleTagFunction = helper.frontend->getPageTitle

    site.10 = TEMPLATE
    site.10 {
        template = FILE
        template.file = fileadmin/mastertemplate.html
        marks {

            LOGO = USER
            LOGO.userFunc = helper.frontend->getLogo

            COPYRIGHT= USER
            COPYRIGHT.userFunc = helper.frontend->getCopyright

            FOOTERMENU < footerMenu
            MAINMENU < mainMenu
            METAMENU < metaMenu

            SUBTEMPLATE = TEMPLATE
            SUBTEMPLATE {
                template = FILE
                template.file.preUserFunc = helper.backend_layout->getLayout
                marks {
                    CONTENT0 < styles.content.get
                    CONTENT1 < styles.content.get
                    CONTENT1.select.where = colPos=1
                }
            }
        }
    }

Normally you would get passed the calling ``ContentObjectRender`` passed into a
public property ``cObj``. When using services for user functions you get passed
the calling content object as third parameter to the method.

Bonus: Hooks
============

The way the user functions are made accessible is also available for hooks,
which use ``callUserFunction()``.

.. code-block:: php

    // ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'hook.news->clearCachePostProc';

If the hook uses ``getUserObj()`` instead, you must add the ``typo.user_obj``
tag to your service.

.. code-block:: php

    // ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typolinkLinkHandler']['tel'] = 'hook.link';

.. note::

    In future iterations Bartacus will abstract the way of defining hooks.
    Either with another service tag or through the Symfony event dispatcher.
