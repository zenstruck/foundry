<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<SomeObject>
 *
 * @method        SomeObject|Proxy create(array|callable $attributes = [])
 * @method static SomeObject|Proxy createOne(array $attributes = [])
 * @method static SomeObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeObjectFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'arrayMandatory' => [],
            'dateTimeImmutableMandatory' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dateTimeMandatory' => self::faker()->dateTime(),
            'floatMandatory' => self::faker()->randomFloat(),
            'intMandatory' => self::faker()->randomNumber(),
            'someMandatoryPropertyWithUnionType' => SomeOtherObjectFactory::new(),
            'someOtherObjectMandatory' => SomeOtherObjectFactory::new(),
            'stringMandatory' => self::faker()->sentence(),
            'stringNullable' => self::faker()->sentence(),
            'stringWithDefault' => self::faker()->sentence(),
        ];
    }

    protected static function getClass(): string
    {
        return SomeObject::class;
    }
}
