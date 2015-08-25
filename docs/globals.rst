===========================
Globals and ``makeInstace``
===========================

Although you have a service container, sometimes you need access to some of the
TYPO3 globals or retrieve TYPO3 classes with ``GeneralUtility::makeInstance()``.
This will clutter your code and is really bad as it makes your services not
testable.

Expression Language to the rescue
=================================

You can not only inject other services and parameters into your services, you
are also able to use some special sort of
`Expression Language <http://symfony.com/doc/current/book/service_container.html#using-the-expression-language>`_
to inject more complex dependencies.

Bartacus supplies a little service bridge to call ``makeInstance()`` and TYPO3
globals from the service expressions.

For example if you define a service which needs an instance of the cHash
calculator, the ``PageRepository`` and a database connection:

.. configuration-block::

    .. code-block:: yaml

        services:
            app.menu:
                class: %app.menu%
                lazy: true
                arguments:
                    - "@=service('typo3').makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator')"
                    - "@=service('typo3').getGlobal('TSFE').sys_page"
                    - "@=service('typo3').getGlobal('TYPO3_DB')"

    .. code-block:: xml

        <services>
            <service id="app.menu" class="%app.menu.class%" lazy="true">
                <argument type="expression">service('typo3').makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator')</argument>
                <argument type="expression">service('typo3').getGlobal('TSFE').sys_page</argument>
                <argument type="expression">service('typo3').getGlobal('TYPO3_DB')</argument>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\ExpressionLanguage\Expression;

        $definition = new Definition($appMenuClass, [
            new Expression('service("typo3").makeInstance("TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator")'),
            new Expression('service("typo3").getGlobal("TSFE").sys_page'),
            new Expression('service("typo3").getGlobal("TYPO3_DB")'),
        ]);
        $definition->setLazy(true);
        $container->setDefinition('app.menu', $definition);

.. note::

    The service example above is marked as a
    `lazy service <http://symfony.com/doc/current/components/dependency_injection/lazy_services.html>`_.
    These is a MUST if you want to use the service as ``typo3.user_func`` or
    ``typo3.user_obj`` to have a correct instance injected. Otherwise your
    service is created too early and you have a wrong cHash calculator and no
    database connection available.
