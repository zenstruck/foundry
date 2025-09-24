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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EmptyConstructorFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\disable_persisting;
use function Zenstruck\Foundry\Persistence\enable_persisting;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenericEntityFactoryTest extends GenericFactoryTestCase
{
    use RequiresORM;

    /**
     * @test
     */
    #[Test]
    public function can_use_factory_with_empty_constructor(): void
    {
        EmptyConstructorFactory::assert()->count(0);

        EmptyConstructorFactory::createOne();

        EmptyConstructorFactory::assert()->count(1);
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_factory_with_empty_constructor_without_persistence(): void
    {
        EmptyConstructorFactory::assert()->count(0);

        disable_persisting();
        EmptyConstructorFactory::createOne();
        enable_persisting();

        EmptyConstructorFactory::assert()->count(0);
    }

    /**
     * @test
     * @dataProvider afterPersistDecideFlushProvider
     *
     * @phpstan-ignore missingType.callable
     */
    #[Test]
    #[DataProvider('afterPersistDecideFlushProvider')]
    public function after_persist_callback_can_decide_if_flush_is_performed_afterwards(callable $callback, string $expected): void
    {
        static::factory()
            ->afterPersist($callback)
            ->create(['prop1' => 'foo']);

        static::factory()::assert()->exists(['prop1' => $expected]);
    }

    public static function afterPersistDecideFlushProvider(): iterable
    {
        yield 'no return will flush' => [
            function(GenericModel $object) {
                $object->setProp1('bar');
            },
            'bar'
        ];

        yield 'return true will flush' => [
            function(GenericModel $object) {
                $object->setProp1('bar');

                return true;
            },
            'bar'
        ];

        yield 'return something else than false will flush' => [
            function(GenericModel $object) {
                $object->setProp1('bar');

                return $object;
            },
            'bar'
        ];

        yield 'return false will not flush' => [
            function(GenericModel $object) {
                $object->setProp1('bar');

                return false;
            },
            'foo'
        ];
    }

    protected static function factory(): GenericEntityFactory
    {
        return GenericEntityFactory::new();
    }
}
