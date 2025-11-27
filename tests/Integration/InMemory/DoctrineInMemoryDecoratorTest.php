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

namespace Zenstruck\Foundry\Tests\Integration\InMemory;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithEmbeddableEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;

use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[AsInMemoryTest]
final class DoctrineInMemoryDecoratorTest extends KernelTestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function it_can_find_by_one_param(): void
    {
        GenericEntityFactory::createMany(2, ['prop1' => 'foo']);
        $expected = GenericEntityFactory::createMany(2, ['prop1' => 'bar']);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'bar']);
        self::assertSame($expected, $found);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_find_by_two_params(): void
    {
        GenericEntityFactory::createMany(2, ['prop1' => 'foo', 'propInteger' => 1]);
        $expected = GenericEntityFactory::createMany(2, ['prop1' => 'bar', 'propInteger' => 2]);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'bar', 'propInteger' => 2]);
        self::assertSame($expected, $found);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_limit_find_by_results(): void
    {
        GenericEntityFactory::createMany(5, ['prop1' => 'bar']);
        GenericEntityFactory::createMany(5, ['prop1' => 'foo']);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'foo'], limit: 2);
        self::assertCount(2, $found);
        foreach ($found as $item) {
            self::assertSame('foo', $item->getProp1());
        }
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_order_find_by_results(): void
    {
        GenericEntityFactory::createMany(10, fn() => ['prop1' => 'foo', 'propInteger' => faker()->numberBetween()]);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'foo'], orderBy: ['propInteger' => 'asc']);
        self::assertCount(10, $found);

        $sorted = $integers = \array_map(static fn(GenericModel $item) => $item->getPropInteger(), $found);
        \sort($sorted);

        self::assertSame($sorted, $integers);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_order_desc_find_by_results(): void
    {
        GenericEntityFactory::createMany(10, fn() => ['prop1' => 'foo', 'propInteger' => faker()->numberBetween()]);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'foo'], orderBy: ['propInteger' => 'desc']);
        self::assertCount(10, $found);
        $sorted = $integers = \array_map(static fn(GenericModel $item) => $item->getPropInteger(), $found);
        \rsort($sorted);

        self::assertSame($sorted, $integers);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_use_offset(): void
    {
        GenericEntityFactory::createMany(4, fn(int $i) => ['prop1' => 'foo', 'propInteger' => $i]);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'foo'], orderBy: ['propInteger' => 'asc'], offset: 1);
        self::assertCount(3, $found);
        $integers = \array_map(static fn(GenericModel $item) => $item->getPropInteger(), $found);

        self::assertSame([2, 3, 4], $integers);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_use_limit_and_offset(): void
    {
        GenericEntityFactory::createMany(4, fn(int $i) => ['prop1' => 'foo', 'propInteger' => $i]);

        $found = GenericEntityFactory::repository()->findBy(['prop1' => 'foo'], orderBy: ['propInteger' => 'asc'], limit: 2, offset: 1);
        self::assertCount(2, $found);
        $integers = \array_map(static fn(GenericModel $item) => $item->getPropInteger(), $found);

        self::assertSame([2, 3], $integers);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_find_one_by(): void
    {
        GenericEntityFactory::createMany(2, ['prop1' => 'foo']);
        [$expected] = GenericEntityFactory::createMany(2, ['prop1' => 'bar']);

        $found = GenericEntityFactory::repository()->findOneBy(['prop1' => 'bar']);
        self::assertSame($expected, $found);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_find_by_entity(): void
    {
        ContactFactory::createMany(2, fn() => ['category' => CategoryFactory::createOne()]);

        $category = CategoryFactory::createOne();
        $contacts = ContactFactory::createMany(2, ['category' => $category]);

        $contactsFound = ContactFactory::repository()->findBy(['category' => $category]);
        self::assertSame($contacts, $contactsFound);
    }

    /**
     * @test
     * @group legacy
     */
    #[Test]
    #[IgnoreDeprecations]
    #[Group('legacy-proxy')]
    public function it_can_find_by_entity_proxified(): void
    {
        ContactFactory::createMany(2, fn() => ['category' => CategoryFactory::createOne()]);

        $category = CategoryFactory::createOne();
        $contacts = ContactFactory::createMany(2, ['category' => $category]);

        $contactsFound = ContactFactory::repository()->findBy(['category' => proxy($category)]);
        self::assertSame($contacts, $contactsFound);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_find_by_embeddable(): void
    {
        $factory = persistent_factory(WithEmbeddableEntity::class);
        $factory->create(['embeddable' => new Embeddable('foo')]);
        $o = $factory->create(['embeddable' => $e = new Embeddable('bar')]);

        $factory::assert()->count(2);
        $found = $factory::repository()->findBy(['embeddable' => $e]);
        self::assertSame([$o], $found);
    }
}
