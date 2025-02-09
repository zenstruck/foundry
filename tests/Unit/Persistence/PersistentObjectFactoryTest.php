<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Persistence;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PersistentObjectFactoryTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function can_create(): void
    {
        $entity1 = GenericEntityFactory::createOne();

        $this->assertInstanceOf(GenericEntity::class, $entity1);
        $this->assertSame('default1', $entity1->getProp1());

        $entity2 = GenericEntityFactory::createOne(['prop1' => 'value']);

        $this->assertInstanceOf(GenericEntity::class, $entity2);
        $this->assertSame('value', $entity2->getProp1());
    }

    /**
     * @test
     */
    #[Test]
    public function find_or_create(): void
    {
        $entity = GenericEntityFactory::findOrCreate(['prop1' => 'foo']);

        $this->assertSame('foo', $entity->getProp1());
    }

    /**
     * @test
     */
    #[Test]
    public function random_or_create(): void
    {
        $entity = GenericEntityFactory::randomOrCreate(['prop1' => 'foo']);

        $this->assertSame('foo', $entity->getProp1());
    }
}
