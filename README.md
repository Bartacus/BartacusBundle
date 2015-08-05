BartacusBundle
==============

Integrates the [Symfony][1] full-stack framework into [TYPO3 CMS][2].
Yes! Really! This package integrates most of the Symfony framework
into TYPO3 CMS, for sure!

Installation & Usage
--------------------

```
composer require bartacus/bartacus-bundle
```

and take a look at the [Bartacus Standard Edition][3] to know which
extra files and configuration is needed to get it running. Basic
Symfony knowledge about how a Symfony project is structured would be
an advantage.

[Read the documentation][4]

Motivation
----------

We don't like pi_base extensions and we don't like Extbase too. So I
had the idea to integrate Symfony to make extensions, which are like
bundles. This has some nice advantages like Twig rendering, service
container and dependency injection and so on.

License
-------

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

[1]: http://symfony.com
[2]: http://typo3.org
[3]: https://github.com/Bartacus/Bartacus-Standard
[4]: http://bartacus.readthedocs.org/
