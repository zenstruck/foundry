<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\FoundryRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->removeUnusedImports();
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->import(__DIR__.'/../../config/foundry-set.php');
};
