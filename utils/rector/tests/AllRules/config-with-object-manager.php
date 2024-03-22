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
use Zenstruck\Foundry\Utils\Rector\FoundrySetList;
use Zenstruck\Foundry\Utils\Rector\PersistenceResolver;

return static function(RectorConfig $rectorConfig): void {
    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->sets([FoundrySetList::UP_TO_FOUNDRY_2]);

    $rectorConfig->singleton(
        PersistenceResolver::class,
        static fn() => new PersistenceResolver(__DIR__.'/entity-manager.php')
    );
};
