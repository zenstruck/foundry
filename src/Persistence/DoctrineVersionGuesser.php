<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence;

use Doctrine\ORM\Mapping\FieldMapping;

final class DoctrineVersionGuesser
{
    public static function isOrmV4(): bool
    {
        return class_exists(FieldMapping::class);
    }
}
