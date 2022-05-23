<?php

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Zenstruck\Foundry\Instantiator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class InstantiatorTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @test
     */
    public function default_instantiate(): void
    {
        $object = (new Instantiator())([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'propD' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('setter D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_snake_case_attributes(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using a differently cased attribute is deprecated, use the same case as the object property instead.');

        $object = (new Instantiator())([
            'prop_a' => 'A',
            'prop_b' => 'B',
            'prop_c' => 'C',
            'prop_d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('setter D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_kebab_case_attributes(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using a differently cased attribute is deprecated, use the same case as the object property instead.');

        $object = (new Instantiator())([
            'prop-a' => 'A',
            'prop-b' => 'B',
            'prop-c' => 'C',
            'prop-d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('setter D', $object->getPropD());
    }

    /**
     * @test
     */
    public function can_leave_off_default_constructor_argument(): void
    {
        $object = (new Instantiator())([
            'propB' => 'B',
        ], InstantiatorDummy::class);

        $this->assertSame('constructor B', $object->getPropB());
        $this->assertNull($object->getPropC());
    }

    /**
     * @test
     */
    public function can_instantiate_object_with_private_constructor(): void
    {
        $object = (new Instantiator())([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'propD' => 'D',
        ], PrivateConstructorInstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('setter B', $object->getPropB());
        $this->assertSame('setter C', $object->getPropC());
        $this->assertSame('setter D', $object->getPropD());
    }

    /**
     * @test
     */
    public function missing_constructor_argument_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing constructor argument "propB" for "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy".');

        (new Instantiator())([], InstantiatorDummy::class);
    }

    /**
     * @test
     */
    public function extra_attributes_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set attribute "extra" for object "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" (not public and no setter).');

        (new Instantiator())([
            'propB' => 'B',
            'extra' => 'foo',
        ], InstantiatorDummy::class);
    }

    /**
     * @test
     */
    public function can_set_attributes_that_should_be_optional(): void
    {
        $object = (new Instantiator())->allowExtraAttributes(['extra'])([
            'propB' => 'B',
            'extra' => 'foo',
        ], InstantiatorDummy::class);

        $this->assertSame('constructor B', $object->getPropB());
    }

    /**
     * @test
     */
    public function extra_attributes_not_defined_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot set attribute "extra2" for object "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" (not public and no setter).');

        (new Instantiator())->allowExtraAttributes(['extra1'])([
            'propB' => 'B',
            'extra1' => 'foo',
            'extra2' => 'bar',
        ], InstantiatorDummy::class);
    }

    /**
     * @test
     * @group legacy
     */
    public function can_prefix_extra_attribute_key_with_optional_to_avoid_exception(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "optional:" attribute prefixes is deprecated, use Instantiator::allowExtraAttributes() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

        $object = (new Instantiator())([
            'propB' => 'B',
            'optional:extra' => 'foo',
        ], InstantiatorDummy::class);

        $this->assertSame('constructor B', $object->getPropB());
    }

    /**
     * @test
     */
    public function can_always_allow_extra_attributes(): void
    {
        $object = (new Instantiator())->allowExtraAttributes()([
            'propB' => 'B',
            'extra' => 'foo',
        ], InstantiatorDummy::class);

        $this->assertSame('constructor B', $object->getPropB());
    }

    /**
     * @test
     */
    public function can_disable_constructor(): void
    {
        $object = (new Instantiator())->withoutConstructor()([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'propD' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('setter B', $object->getPropB());
        $this->assertSame('setter C', $object->getPropC());
        $this->assertSame('setter D', $object->getPropD());
    }

    /**
     * @test
     */
    public function can_set_attributes_that_should_be_force_set(): void
    {
        $object = (new Instantiator())->withoutConstructor()->alwaysForceProperties(['propD'])([
            'propB' => 'B',
            'propD' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('setter B', $object->getPropB());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function prefixing_attribute_key_with_force_sets_the_property_directly(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

        $object = (new Instantiator())([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'force:propD' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function prefixing_snake_case_attribute_key_with_force_sets_the_property_directly(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

        $object = (new Instantiator())([
            'prop_a' => 'A',
            'prop_b' => 'B',
            'prop_c' => 'C',
            'force:prop_d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function prefixing_kebab_case_attribute_key_with_force_sets_the_property_directly(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

        $object = (new Instantiator())([
            'prop-a' => 'A',
            'prop-b' => 'B',
            'prop-c' => 'C',
            'force:prop-d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function prefixing_invalid_attribute_key_with_force_throws_exception(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "force:" property prefixes is deprecated, use Instantiator::alwaysForceProperties() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" does not have property "extra".');

        (new Instantiator())([
            'propB' => 'B',
            'force:extra' => 'foo',
        ], InstantiatorDummy::class);
    }

    /**
     * @test
     */
    public function can_use_force_set_and_get(): void
    {
        $object = new InstantiatorDummy('B');

        $this->assertNull(Instantiator::forceGet($object, 'propE'));

        Instantiator::forceSet($object, 'propE', 'value');

        $this->assertSame('value', Instantiator::forceGet($object, 'propE'));
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_force_set_and_get_with_kebab_and_snake_case(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using a differently cased attribute is deprecated, use the same case as the object property instead.');

        $object = new InstantiatorDummy('B');

        $this->assertNull(Instantiator::forceGet($object, 'propE'));
        $this->assertNull(Instantiator::forceGet($object, 'prop_e'));
        $this->assertNull(Instantiator::forceGet($object, 'prop-e'));

        Instantiator::forceSet($object, 'propE', 'camel');

        $this->assertSame('camel', Instantiator::forceGet($object, 'propE'));
        $this->assertSame('camel', Instantiator::forceGet($object, 'prop_e'));
        $this->assertSame('camel', Instantiator::forceGet($object, 'prop-e'));

        Instantiator::forceSet($object, 'prop_e', 'snake');

        $this->assertSame('snake', Instantiator::forceGet($object, 'propE'));
        $this->assertSame('snake', Instantiator::forceGet($object, 'prop_e'));
        $this->assertSame('snake', Instantiator::forceGet($object, 'prop-e'));

        Instantiator::forceSet($object, 'prop-e', 'kebab');

        $this->assertSame('kebab', Instantiator::forceGet($object, 'propE'));
        $this->assertSame('kebab', Instantiator::forceGet($object, 'prop_e'));
        $this->assertSame('kebab', Instantiator::forceGet($object, 'prop-e'));
    }

    /**
     * @test
     */
    public function force_set_throws_exception_for_invalid_property(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" does not have property "invalid".');

        Instantiator::forceSet(new InstantiatorDummy('B'), 'invalid', 'value');
    }

    /**
     * @test
     */
    public function force_get_throws_exception_for_invalid_property(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" does not have property "invalid".');

        Instantiator::forceGet(new InstantiatorDummy('B'), 'invalid');
    }

    /**
     * @test
     */
    public function can_use_always_force_mode(): void
    {
        $object = (new Instantiator())->alwaysForceProperties()([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'propD' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_always_force_mode_allows_snake_case(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using a differently cased attribute is deprecated, use the same case as the object property instead.');

        $object = (new Instantiator())->alwaysForceProperties()([
            'prop_a' => 'A',
            'prop_b' => 'B',
            'prop_c' => 'C',
            'prop_d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     * @group legacy
     */
    public function can_use_always_force_mode_allows_kebab_case(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using a differently cased attribute is deprecated, use the same case as the object property instead.');

        $object = (new Instantiator())->alwaysForceProperties()([
            'prop-a' => 'A',
            'prop-b' => 'B',
            'prop-c' => 'C',
            'prop-d' => 'D',
        ], InstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     */
    public function always_force_mode_throws_exception_for_extra_attributes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "Zenstruck\Foundry\Tests\Unit\InstantiatorDummy" does not have property "extra".');

        (new Instantiator())->alwaysForceProperties()([
            'propB' => 'B',
            'extra' => 'foo',
        ], InstantiatorDummy::class);
    }

    /**
     * @test
     * @group legacy
     */
    public function always_force_mode_allows_optional_attribute_name_prefix(): void
    {
        $this->expectDeprecation('Since zenstruck\foundry 1.5.0: Using "optional:" attribute prefixes is deprecated, use Instantiator::allowExtraAttributes() instead (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#instantiation).');

        $object = (new Instantiator())->alwaysForceProperties()([
            'propB' => 'B',
            'propD' => 'D',
            'optional:extra' => 'foo',
        ], InstantiatorDummy::class);

        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     */
    public function always_force_mode_with_allow_extra_attributes_mode(): void
    {
        $object = (new Instantiator())->allowExtraAttributes()->alwaysForceProperties()([
            'propB' => 'B',
            'propD' => 'D',
            'extra' => 'foo',
        ], InstantiatorDummy::class);

        $this->assertSame('D', $object->getPropD());
    }

    /**
     * @test
     */
    public function always_force_mode_can_set_parent_class_properties(): void
    {
        $object = (new Instantiator())->alwaysForceProperties()([
            'propA' => 'A',
            'propB' => 'B',
            'propC' => 'C',
            'propD' => 'D',
            'propE' => 'E',
        ], ExtendedInstantiatorDummy::class);

        $this->assertSame('A', $object->propA);
        $this->assertSame('A', $object->getPropA());
        $this->assertSame('constructor B', $object->getPropB());
        $this->assertSame('constructor C', $object->getPropC());
        $this->assertSame('D', $object->getPropD());
        $this->assertSame('E', Instantiator::forceGet($object, 'propE'));
    }

    /**
     * @test
     */
    public function invalid_attribute_type_with_allow_extra_enabled_throws_exception(): void
    {
        $this->expectException(\Throwable::class);

        (new Instantiator())->allowExtraAttributes()([
            'propB' => 'B',
            'propF' => 'F',
        ], InstantiatorDummy::class);
    }

    /**
     * @test
     */
    public function can_set_variadic_constructor_attributes(): void
    {
        $object = (new Instantiator())([
            'propA' => 'A',
            'propB' => ['B', 'C', 'D'],
        ], VariadicInstantiatorDummy::class);

        $this->assertSame('constructor A', $object->getPropA());
        $this->assertSame(['B', 'C', 'D'], $object->getPropB());
    }

    /**
     * @test
     */
    public function missing_variadic_argument_thtrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing constructor argument "propB" for "Zenstruck\Foundry\Tests\Unit\VariadicInstantiatorDummy".');
        $object = (new Instantiator())([
            'propA' => 'A',
        ], VariadicInstantiatorDummy::class);
    }
}

class InstantiatorDummy
{
    public $propA;
    public $propD;
    private $propB;
    private $propC;
    private $propE;
    private $propF;

    public function __construct($propB, $propC = null)
    {
        $this->propB = 'constructor '.$propB;

        if ($propC) {
            $this->propC = 'constructor '.$propC;
        }
    }

    public function getPropA()
    {
        return $this->propA;
    }

    public function getPropB()
    {
        return $this->propB;
    }

    public function setPropB($propB)
    {
        $this->propB = 'setter '.$propB;
    }

    public function getPropC()
    {
        return $this->propC;
    }

    public function setPropC($propC)
    {
        $this->propC = 'setter '.$propC;
    }

    public function getPropD()
    {
        return $this->propD;
    }

    public function setPropD($propD)
    {
        $this->propD = 'setter '.$propD;
    }

    public function setPropF(object $propF)
    {
        $this->propF = $propF;
    }
}

class ExtendedInstantiatorDummy extends InstantiatorDummy
{
}

class PrivateConstructorInstantiatorDummy extends InstantiatorDummy
{
    private function __construct()
    {
        parent::__construct('B', 'C');
    }
}

class VariadicInstantiatorDummy
{
    private $propA;
    private $propB;

    public function __construct($propA, ...$propB)
    {
        $this->propA = 'constructor '.$propA;
        $this->propB = $propB;
    }

    public function getPropA()
    {
        return $this->propA;
    }

    public function getPropB()
    {
        return $this->propB;
    }
}
