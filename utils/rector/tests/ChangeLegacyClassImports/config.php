<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeLegacyClassImports;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ChangeLegacyClassImports::class);
};
