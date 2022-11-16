<?php

namespace App\Factory;

use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<SomeObject>
 *
 * @method SomeObject|Proxy create(array|callable $attributes = [])
 * @method static SomeObject|Proxy createOne(array $attributes = [])
 * @method static SomeObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeObject[]|Proxy[] createSequence(array|callable $sequence)
 */
final class SomeObjectFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'stringMandatory' => self::faker()->text(),
            'intMandatory' => self::faker()->randomNumber(),
            'floatMandatory' => self::faker()->randomFloat(),
            'arrayMandatory' => [],
            'dateTimeMandatory' => self::faker()->dateTime(),
            'dateTimeImmutableMandatory' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'someOtherObjectMandatory' => null, // TODO add Zenstruck\Foundry\Tests\Fixtures\Object\SomeOtherObject value manually
            'someMandatoryPropertyWithUnionType' => null, // TODO add value manually
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
