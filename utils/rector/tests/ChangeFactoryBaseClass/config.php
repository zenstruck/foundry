<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeFactoryBaseClass;
use Zenstruck\Foundry\Utils\Rector\PersistenceResolver;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ChangeFactoryBaseClass::class);
};
