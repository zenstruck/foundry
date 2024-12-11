<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class UsingRelationships
{
    public function __construct(
        /** @var class-string */
        public readonly string $class,
        public readonly array $relationShips
    )
    {
    }
}
