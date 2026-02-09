<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit;

use Faker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\UnitTestConfig;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EmptyConstructorFactory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\faker;

final class FactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        // neutralize custom configuration added in some tests
        UnitTestConfig::configure();
        Configuration::boot(UnitTestConfig::build());
    }

    #[Test]
    public function can_register_custom_faker(): void
    {
        $defaultFaker = faker();

        UnitTestConfig::configure(faker: Faker\Factory::create());
        Configuration::boot(UnitTestConfig::build());

        $this->assertNotSame(faker(), $defaultFaker);
    }

    #[Test]
    public function can_use_arrays_for_attribute_values(): void
    {
        $object = new class {
            public mixed $value = null;
        };

        $factory = factory($object::class)->create(['value' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar'], $factory->value);
    }

    #[Test]
    public function can_register_default_instantiator(): void
    {
        UnitTestConfig::configure(instantiator: static fn(): Object1 => new Object1(
            'different prop1', 'different prop2'
        ));
        Configuration::boot(UnitTestConfig::build());

        $object = factory(Object1::class, ['prop1' => 'prop1'])->create();

        $this->assertSame('different prop1-constructor', $object->getProp1());
        $this->assertSame('different prop2-constructor', $object->getProp2());
    }

    #[Test]
    public function instantiating_with_factory_attribute_instantiates_the_factory(): void
    {
        $object = ContactFactory::createOne([
            'category' => CategoryFactory::new(),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    #[Test]
    public function can_use_factory_with_empty_constructor(): void
    {
        $genericEntity = EmptyConstructorFactory::createOne();

        self::assertInstanceOf(GenericEntity::class, $genericEntity);
    }
}
