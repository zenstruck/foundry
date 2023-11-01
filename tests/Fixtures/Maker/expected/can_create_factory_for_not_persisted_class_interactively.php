<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'arrayMandatory' => [],
            'dateTimeImmutableMandatory' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dateTimeMandatory' => self::faker()->dateTime(),
            'floatMandatory' => self::faker()->randomFloat(),
            'intMandatory' => self::faker()->randomNumber(),
            'someMandatoryPropertyWithUnionType' => null, // TODO add value manually
            'someOtherObjectMandatory' => SomeOtherObjectFactory::new(),
            'stringMandatory' => self::faker()->sentence(),
            'stringNullable' => self::faker()->sentence(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            ->withoutPersisting()
            // ->afterInstantiate(function(SomeObject $someObject): void {})
        ;
    }

    protected static function getClass(): string
    {
        return SomeObject::class;
    }
}
