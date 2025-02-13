<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class InverseRelationshipMetadata
{
    public function __construct(
        public readonly string $inverseField,
        public readonly bool $isCollection,
        public readonly string|null $collectionIndexedBy,
    ) {
    }
}
