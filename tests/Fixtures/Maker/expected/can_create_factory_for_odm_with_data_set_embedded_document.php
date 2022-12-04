<?php

namespace App\Factory;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\UserFactory;

/**
 * @extends ModelFactory<ODMComment>
 *
 * @method        ODMComment|Proxy create(array|callable $attributes = [])
 * @method static ODMComment|Proxy createOne(array $attributes = [])
 * @method static ODMComment|Proxy find(object|array|mixed $criteria)
 * @method static ODMComment|Proxy findOrCreate(array $attributes)
 * @method static ODMComment|Proxy first(string $sortedField = 'id')
 * @method static ODMComment|Proxy last(string $sortedField = 'id')
 * @method static ODMComment|Proxy random(array $attributes = [])
 * @method static ODMComment|Proxy randomOrCreate(array $attributes = [])
 * @method static ODMComment[]|Proxy[] all()
 * @method static ODMComment[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ODMComment[]|Proxy[] createSequence(array|callable $sequence)
 * @method static ODMComment[]|Proxy[] findBy(array $attributes)
 * @method static ODMComment[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ODMComment[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class ODMCommentFactory extends ModelFactory
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
            'approved' => self::faker()->boolean(),
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'user' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(ODMComment $oDMComment): void {})
        ;
    }

    protected static function getClass(): string
    {
        return ODMComment::class;
    }
}
