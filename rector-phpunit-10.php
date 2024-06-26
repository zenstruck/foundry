<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/tests',
    ])
    ->withSets([
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
