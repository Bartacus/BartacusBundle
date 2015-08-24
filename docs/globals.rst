===========================
Globals and ``makeInstace``
===========================

Altough you have a service container, sometimes you need access to some of the
TYPO3 globals or retrieve TYPO3 classes with ``GeneralUtility::makeInstance()``.
This will clutter your code and is really bad as it makes your services not
testable.

Expression Language to the rescue
=================================

You can not only inject other services and parameters into your services, you
are also able to use some special sort of
`Expression Language <http://symfony.com/doc/current/book/service_container.html#using-the-expression-language>`_
to inject more complex dependencies.

Baratacus supplies a little service bridge to call ``makeInstance()`` and TYPO3
globals from the service expressions.

For example if you define a service which needs an instance of the cHash
calculator, the ``PageRepository`` and a database connection:

.. configuration-block::

    .. code-block:: yaml

        services:
            app.menu:
                class: %app.menu%
                arguments:
                    - "@=service('typo3').makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator')"
                    - "@=service('typo3').getGlobal('TSFE').sys_page"
                    - "@=service('typo3').getGlobal('TYPO3_DB')"

    .. code-block:: xml

        <services>
            <service id="app.menu" class="%app.menu.class%">
                <argument type="expression">service('typo3').makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator')</argument>
                <argument type="expression">service('typo3').getGlobal('TSFE').sys_page</argument>
                <argument type="expression">service('typo3').getGlobal('TYPO3_DB')</argument>
            </service>
        </services>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\ExpressionLanguage\Expression;

        $container->setDefinition('app.menu', new Definition($appMenuClass, [
            new Expression('service("typo3").makeInstance("TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator")'),
            new Expression('service("typo3").getGlobal("TSFE").sys_page'),
            new Expression('service("typo3").getGlobal("TYPO3_DB")'),
        ]));
