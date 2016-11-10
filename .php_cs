<?php

/*
 * This file is part of the BartacusBundle.
 *
 * The BartacusBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The BartacusBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with the BartacusBundle. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

$finder = Symfony\CS\Finder::create()
    ->exclude('vendor')
    ->in(__DIR__)
;

$header = <<<EOF
This file is part of the BartacusBundle.

The BartacusBundle is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

The BartacusBundle is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with the BartacusBundle. If not, see <http://www.gnu.org/licenses/>.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config::create()
    ->fixers([
        'combine_consecutive_unsets',
        'header_comment',
        'no_useless_else',
        'no_useless_return',
        'ordered_use',
        'php_unit_construct',
        'php_unit_dedicate_assert',
        'php_unit_strict',
        'phpdoc_order',
        'short_array_syntax',
        'silenced_deprecation_error',
        'strict',
        'strict_param',
    ])
    ->finder($finder)
    ->setUsingCache(true)
;
