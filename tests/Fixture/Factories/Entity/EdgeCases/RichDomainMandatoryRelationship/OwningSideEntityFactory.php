<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EdgeCases\RichDomainMandatoryRelationship;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RichDomainMandatoryRelationship\OwningSideEntity;

/**
 * @extends PersistentObjectFactory<OwningSideEntity>
 */
final class OwningSideEntityFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return OwningSideEntity::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'main' => InversedSideEntityFactory::new(),
        ];
    }
}
