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

use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EmptyConstructorFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
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

    protected static function factory(): GenericEntityFactory
    {
        return GenericEntityFactory::new();
    }

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
}
