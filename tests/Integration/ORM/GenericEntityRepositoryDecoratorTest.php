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

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\LazyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericRepositoryDecoratorTestCase;

use function Zenstruck\Foundry\Persistence\repository;

#[RequiresEnvironmentVariable('DATABASE_URL')]
class GenericEntityRepositoryDecoratorTest extends GenericRepositoryDecoratorTestCase
{
    #[Test]
    public function can_call_find_by_with_multiple_values(): void
    {
        [$object1, $object2] = $this->factory()->sequence([['prop1' => 'foo'], ['prop1' => 'bar']])->create();

        $repository = repository($this->modelClass());

        $this->assertSame(LazyObjectFactory::unwrap($object1), LazyObjectFactory::unwrap($repository->find(['prop1' => 'foo'])));
        $this->assertSame(LazyObjectFactory::unwrap($object2), LazyObjectFactory::unwrap($repository->find(['prop1' => 'bar'])));

        $by = $repository->findBy(['prop1' => ['foo', 'bar']]);
        $this->assertSame(LazyObjectFactory::unwrap([$object1, $object2]), LazyObjectFactory::unwrap($by));
    }

    protected function factory(): PersistentObjectFactory
    {
        return GenericEntityFactory::new();
    }
}
