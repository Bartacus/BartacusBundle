============
Installation
============

Requirements
============

* PHP 7
* Symfony 3
* TYPO3 8.3 in `Composer mode`_

New TYPO3 project
=================

t.b.c.

.. todo::

    Include link to Bartacus Standard installation after they are created.

Existing TYPO3 installation
===========================

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require bartacus/bartacus-bundle "^1.0@dev"
```

This command requires you to have Composer installed globally, as explained
in the `installation chapter`_ of the Composer documentation.

Step 2: Install the Bundle and Symfony
--------------------------------------

Now take a look at the `Bartacus Standard Edition`_ to know which files,
directories and configuration are required to create to get it running. The
most important file is ``web/typo3conf/AdditionalConfiguration.php`` where the main
part of the Symfony kernel is initialised and ``app/AppKernel.php`` where all
Symfony bundles are loaded.

.. _`Composer mode`: https://wiki.typo3.org/Composer#Composer_Mode
.. _`installation chapter`: https://getcomposer.org/doc/00-intro.md
.. _`Bartacus Standard Edition`: https://github.com/Bartacus/Bartacus-Standard
