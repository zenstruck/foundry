<?php

namespace App\Tests\Factory;

use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Tag>
 *
 * @method Tag|Proxy create(array|callable $attributes = [])
 * @method static Tag|Proxy createOne(array $attributes = [])
 * @method static Tag|Proxy find(object|array|mixed $criteria)
 * @method static Tag|Proxy findOrCreate(array $attributes)
 * @method static Tag|Proxy first(string $sortedField = 'id')
 * @method static Tag|Proxy last(string $sortedField = 'id')
 * @method static Tag|Proxy random(array $attributes = [])
 * @method static Tag|Proxy randomOrCreate(array $attributes = [])
 * @method static Tag[]|Proxy[] all()
 * @method static Tag[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Tag[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Tag[]|Proxy[] findBy(array $attributes)
 * @method static Tag[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Tag[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class TagFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(255),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Tag $tag): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Tag::class;
    }
}
