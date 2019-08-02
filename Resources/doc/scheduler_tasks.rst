===============
Scheduler Tasks
===============

TYPO3 scheduler tasks can't be containerized or get other services injected, because they are serialized into the database. For this case, Bartacus created a new ``TaskInterface`` and creates a proxy class to your task which is then configured as the task class for the scheduler.

.. note::

    Since the proxy class uses a generated class name with a hash, you have to call the ``taskProxyUpdate`` wizard to fix the scheduler table. The hash and generated proxy class changes on new versions of Bartacus, the proxy manager or if you change the implementing interfaces.

    .. code-block:: bash

        vendor/bin/typo3cms upgrade:wizard taskProxyUpdate

    The upgrade wizard is a repeatable wizard and executed every time you run the whole upgrade wizard process.

Basic Usage
===========

Implementing the new ``TaskInterface`` requires to configure task inline within the class. The configuration used to do in ``ext_localconf.php`` is now done via the ``getConfiguration()`` method of the task. The default ``extension`` key in the configuration is ``app``, you don't need to explicitly configure it.

The ``execute()`` method gets passed an ``$options`` array which is explained later.

.. code-block:: php

    <?php

    namespace App\Task;

    use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;

    class AcmeTask implements TaskInterface
    {
        public static function getConfiguration(): array
        {
            return [
                'title' => 'LLL:EXT:app/Resources/Private/Language/locallang.xlf:task.acme.title',
                'description' => 'LLL:EXT:app/Resources/Private/Language/locallang.xlf:task.acme.description',
            ];
        }

        public function execute(array $options): bool
        {
            // do the task stuff

            return true; // or false depending on the task result
        }
    }

If you use auto configuration you're done now. If not, you must tag the task service with the ``bartacus.scheduler_task`` tag.

Advanced Usage
==============

TYPO3 provides a few features for implementing task. Bartacus provides for each of them a separate interface.

``AdditionalInformationProviderInterface``
------------------------------------------

Simple interface to provide some output to the backend to differentiate the same scheduler task with different configs.

.. code-block:: php

    <?php

    namespace App\Task;

    use Bartacus\Bundle\BartacusBundle\Scheduler\AdditionalInformationProviderInterface;
    use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;

    final class AcmeTask implements TaskInterface, AdditionalInformationProviderInterface
    {
        public function getAdditionalInformation(array $options): string
        {
            return sprintf('Table: %s', $options['table']);
        }

        // ...
    }

``ProgressProviderInterface``
-----------------------------

Simple interface to provide a progress to the backend if the task e.g. indexes something. Return the progress as a two decimal precision float. f.e. ``44.87``.

.. code-block:: php

    <?php

    namespace App\Task;

    use Bartacus\Bundle\BartacusBundle\Scheduler\ProgressProviderInterface;
    use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;

    final class AcmeTask implements TaskInterface, ProgressProviderInterface
    {
        public function getProgress(array $options): float
        {
            // calculate the progress

            return $progress;
        }

        // ...
    }

``OptionsProviderInterface``
----------------------------

Together with an additional fields provider to configure options in the scheduler backend. Those configured and saved options of the scheduler task are passed as ``$options`` array to most other methods.

.. code-block:: php

    <?php

    namespace App\Task;

    use Bartacus\Bundle\BartacusBundle\Scheduler\OptionsProviderInterface;
    use Bartacus\Bundle\BartacusBundle\Scheduler\TaskInterface;

    final class AcmeTask implements TaskInterface, OptionsProviderInterface
    {
        public static function getConfiguration(): array
        {
            return [
                'title' => 'LLL:EXT:app/Resources/Private/Language/locallang.xlf:task.acme.title',
                'description' => 'LLL:EXT:app/Resources/Private/Language/locallang.xlf:task.acme.description',
                'additionalFields' => AcmeAdditionalFieldProvider::class,
            ];
        }

        public function configureOptions(OptionsResolver $resolver)
        {
            $resolver->setRequired('foo');
        }

        // ...
    }

In the additional field provider you can access and save the options in the proxy task object itself. But using an ``OptionsResolver`` you should validate them first and report resolver exceptions via a flash message to the user.

If you don't use autoconfiguration, you need to tag the field provider class with the ``bartacus.make_instance`` tag.

.. code-block:: php

    <?php

    namespace App\Task;

    use Bartacus\Bundle\BartacusBundle\Scheduler\OptionsInterface
    use Bartacus\Bundle\BartacusBundle\Scheduler\OptionsInterface;
    use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use TYPO3\CMS\Core\Messaging\FlashMessage;
    use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
    use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
    use TYPO3\CMS\Scheduler\Task\AbstractTask;
    use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

    final class AcmeAdditionalFieldProvider extends AbstractAdditionalFieldProvider
    {
        private $proxiedTask;

        public function __construct(AcmeTask $proxiedTask)
        {
            $this->proxiedTask = $proxiedTask;
        }

        /**
         * @param OptionsInterface $task
         */
        public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
        {
            return [
                'task_acme_foo' => $this->getFooAdditionalField($taskInfo, $task, $schedulerModule),
            ];
        }

        /**
         * @param OptionsInterface $task
         */
        private function getFooAdditionalField(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
        {
            // access the existing options like the following
            $foo = $task->getOptions()['foo'];

            // configure field

            return $fieldConfiguration;
        }

        public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
        {
            $optionsResolver = new OptionsResolver();
            $this->proxiedTask->configureOptions($optionsResolver);

            try {
                $optionsResolver->resolve([
                    'foo' => $submittedData['task_acme_foo'],
                ]);
            } catch (ExceptionInterface $e) {
                $this->addMessage($e->getMessage(), FlashMessage::ERROR);

                return false;
            }

            return true;
        }

        /**
         * @param OptionsInterface $task
         */
        public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
        {
            $task->setOptions([
                'foo' => $submittedData['task_acme_foo'],
            ]);
        }
    }
