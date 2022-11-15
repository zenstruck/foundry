<?php

namespace App\Factory;

use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Comment>
 *
 * @method Comment|Proxy create(array|callable $attributes = [])
 * @method static Comment|Proxy createOne(array $attributes = [])
 * @method static Comment|Proxy find(object|array|mixed $criteria)
 * @method static Comment|Proxy findOrCreate(array $attributes)
 * @method static Comment|Proxy first(string $sortedField = 'id')
 * @method static Comment|Proxy last(string $sortedField = 'id')
 * @method static Comment|Proxy random(array $attributes = [])
 * @method static Comment|Proxy randomOrCreate(array $attributes = [])
 * @method static list<Comment>|list<Proxy> all()
 * @method static list<Comment>|list<Proxy> createMany(int $number, array|callable $attributes = [])
 * @method static list<Comment>|list<Proxy> createSequence(array|callable $sequence)
 * @method static list<Comment>|list<Proxy> findBy(array $attributes)
 * @method static list<Comment>|list<Proxy> randomRange(int $min, int $max, array $attributes = [])
 * @method static list<Comment>|list<Proxy> randomSet(int $number, array $attributes = [])
 */
final class CommentFactory extends ModelFactory
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
            'user' => null, // TODO add ONE ODM type manually
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'approved' => self::faker()->boolean(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Comment $comment): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Comment::class;
    }
}
