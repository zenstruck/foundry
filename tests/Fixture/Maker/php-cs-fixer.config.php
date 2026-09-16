<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// maker-bundle < 1.68 formats the generated files with php-cs-fixer:
// disable every rule, so that the generated code is asserted as is, whatever the maker-bundle version
return (new PhpCsFixer\Config())->setRules([]);
