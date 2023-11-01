<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeInstantiatorMethodCalls;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->removeUnusedImports(true);
    $rectorConfig->rule(ChangeInstantiatorMethodCalls::class);
};
