<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DoctrineCascadeRelationshipMetadata
{
    public function __construct(
        public readonly string $class,
        public readonly string $field,
        public readonly bool $cascade,
    )
    {
    }
}
