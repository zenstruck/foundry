<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeLegacyClassImports;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ChangeLegacyClassImports::class);
};
