<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeFunctionsCalls;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->removeUnusedImports(true);
    $rectorConfig->rule(ChangeFunctionsCalls::class);
};
