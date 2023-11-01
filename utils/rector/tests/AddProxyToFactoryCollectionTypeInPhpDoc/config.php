<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\AddProxyToFactoryCollectionTypeInPhpDoc;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(AddProxyToFactoryCollectionTypeInPhpDoc::class);
};
