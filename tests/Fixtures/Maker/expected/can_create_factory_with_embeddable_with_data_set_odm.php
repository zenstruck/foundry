<?php

namespace App\Factory;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;

/**
 * @extends ModelFactory<ODMPost>
 *
 * @method        ODMPost|Proxy create(array|callable $attributes = [])
 * @method static ODMPost|Proxy createOne(array $attributes = [])
 * @method static ODMPost|Proxy find(object|array|mixed $criteria)
 * @method static ODMPost|Proxy findOrCreate(array $attributes)
 * @method static ODMPost|Proxy first(string $sortedField = 'id')
 * @method static ODMPost|Proxy last(string $sortedField = 'id')
 * @method static ODMPost|Proxy random(array $attributes = [])
 * @method static ODMPost|Proxy randomOrCreate(array $attributes = [])
 * @method static ODMPost[]|Proxy[] all()
 * @method static ODMPost[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ODMPost[]|Proxy[] createSequence(array|callable $sequence)
 * @method static ODMPost[]|Proxy[] findBy(array $attributes)
 * @method static ODMPost[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ODMPost[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class ODMPostFactory extends ModelFactory
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
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'publishedAt' => self::faker()->dateTime(),
            'shortDescription' => self::faker()->text(),
            'title' => self::faker()->text(),
            'user' => ODMUserFactory::new(),
            'viewCount' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(ODMPost $oDMPost): void {})
        ;
    }

    protected static function getClass(): string
    {
        return ODMPost::class;
    }
}
