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

namespace Zenstruck\Foundry\Tests\Unit;

use Faker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\UnitTestConfig;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\proxy_factory;

final class FactoryTest extends TestCase
{
    use Factories;

    protected function tearDown(): void
    {
        // neutralize custom configuration added in some tests
        UnitTestConfig::configure();
        Configuration::boot(UnitTestConfig::build());
    }

    /**
     * @test
     */
    #[Test]
    public function can_register_custom_faker(): void
    {
        $defaultFaker = faker();

        UnitTestConfig::configure(faker: Faker\Factory::create());
        Configuration::boot(UnitTestConfig::build());

        $this->assertNotSame(faker(), $defaultFaker);
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_arrays_for_attribute_values(): void
    {
        $object = new class {
            public mixed $value = null;
        };

        $factory = factory($object::class)->create(['value' => ['foo' => 'bar']]);

        $this->assertSame(['foo' => 'bar'], $factory->value);
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_user_defined_proxy_persistent_factory_in_unit_test(): void
    {
        $object = GenericProxyEntityFactory::createOne();

        $this->assertInstanceOf(GenericEntity::class, $object);
        $this->assertInstanceOf(Proxy::class, $object);
    }

    /**
     * @test
     */
    #[Test]
    public function can_use_user_anonymous_proxy_persistent_factory_in_unit_test(): void
    {
        $object = proxy_factory(GenericEntity::class, ['prop1' => 'prop1'])->create();

        $this->assertInstanceOf(GenericEntity::class, $object);
        $this->assertInstanceOf(Proxy::class, $object);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    #[Test]
    public function proxy_attributes_can_be_used_in_unit_test(): void
    {
        $object = ProxyContactFactory::createOne([
            'category' => proxy(new Category('name')),
            'address' => ProxyAddressFactory::new(),
        ]);

        $this->assertInstanceOf(Contact::class, $object);
    }

    /**
     * @test
     */
    #[Test]
    public function instantiating_with_factory_attribute_instantiates_the_factory(): void
    {
        $object = ContactFactory::createOne([
            'category' => CategoryFactory::new(),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function instantiating_with_proxy_attribute_normalizes_to_underlying_object(): void
    {
        $object = ProxyContactFactory::createOne([
            'category' => proxy(new Category('name')),
        ]);

        $this->assertInstanceOf(Category::class, $object->getCategory());
    }
}
