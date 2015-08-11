========
Overview
========

Requirements
============

* PHP 5.4
* Symfony 2.7

.. _installation:

Installation
============

The only way to install Bartacus is  with `Composer <http://getcomposer.org>`_.

.. code-block:: bash

    composer require bartacus/bartacus-bundle ~0.3.0@dev

Now take a look at the
`Bartacus Standard Edition <https://github .com/Bartacus/Bartacus-Standard>`_
to know which extra files and configuration is needed to get it running. The
most important file is ``typo3conf/AdditionalConfiguration.php`` where the main
part of Bartacus is initialised and ``fileadmin/app/AppKernel.php`` where all
Symfony bundles and extensions which are turned into bundles are loaded.

License
=======

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
