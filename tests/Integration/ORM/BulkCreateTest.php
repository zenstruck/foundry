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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @see https://github.com/zenstruck/foundry/issues/883
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BulkCreateTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /** @test */
    #[Test]
    public function it_creates_every_object(): void
    {
        GenericEntityFactory::new()->many(25)->bulkCreate(batchSize: 10);

        GenericEntityFactory::assert()->count(25);
    }

    /** @test */
    #[Test]
    public function it_never_holds_more_than_one_batch_in_the_unit_of_work(): void
    {
        $maxSize = 0;

        GenericEntityFactory::new()
            ->afterPersist(function() use (&$maxSize): void {
                $maxSize = \max($maxSize, self::unitOfWorkSize());
            })
            ->many(25)
            ->bulkCreate(batchSize: 10)
        ;

        self::assertSame(10, $maxSize);
    }

    /** @test */
    #[Test]
    public function it_clears_the_object_manager_once_done(): void
    {
        GenericEntityFactory::new()->many(25)->bulkCreate(batchSize: 10);

        self::assertSame(0, self::unitOfWorkSize());
    }

    /** @test */
    #[Test]
    public function it_forwards_attributes_and_their_index(): void
    {
        GenericEntityFactory::new()->many(3)->bulkCreate(static fn(int $i) => ['prop1' => "value{$i}"], batchSize: 2);

        $values = \array_map(static fn($entity) => $entity->getProp1(), GenericEntityFactory::repository()->findAll());
        \sort($values);

        self::assertSame(['value1', 'value2', 'value3'], $values);
    }

    /** @test */
    #[Test]
    public function it_can_be_used_with_a_sequence(): void
    {
        GenericEntityFactory::new()
            ->sequence([['prop1' => 'a'], ['prop1' => 'b'], ['prop1' => 'c']])
            ->bulkCreate(batchSize: 2)
        ;

        GenericEntityFactory::assert()->count(3);
    }

    /** @test */
    #[Test]
    public function it_throws_when_the_factory_does_not_persist(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('bulkCreate() can only be used with a factory which persists its objects.');

        GenericEntityFactory::new()->withoutPersisting()->many(2)->bulkCreate();
    }

    private static function unitOfWorkSize(): int
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        return $em->getUnitOfWork()->size();
    }
}
