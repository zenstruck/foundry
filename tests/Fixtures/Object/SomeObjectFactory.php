<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @extends PersistentProxyObjectFactory<SomeObject>
 *
 * @method        SomeObject|Proxy     create(array|callable $attributes = [])
 * @method static SomeObject|Proxy     createOne(array $attributes = [])
 * @method static SomeObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeObjectFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SomeObject::class;
    }

    protected function defaults(): array|callable
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
}
