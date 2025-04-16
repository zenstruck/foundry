<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence\Relationship;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class OneToManyRelationship implements RelationshipMetadata
{
    public function __construct(
        private readonly string $inverseField,
        public readonly ?string $collectionIndexedBy,
    ) {
    }

    public function inverseField(): string
    {
        return $this->inverseField;
    }
}
