<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Zenstruck\Foundry\Utils\Rector\ChangeStaticFactoryFakerCalls;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(ChangeStaticFactoryFakerCalls::class);
};
