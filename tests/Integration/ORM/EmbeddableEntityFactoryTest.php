<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithEmbeddableEntity;
use Zenstruck\Foundry\Tests\Integration\Persistence\EmbeddableFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmbeddableEntityFactoryTest extends EmbeddableFactoryTestCase
{
    use RequiresORM;

    protected function withEmbeddableFactory(): PersistentObjectFactory
    {
        return persistent_factory(WithEmbeddableEntity::class); // @phpstan-ignore-line
    }
}
