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
 * @method static ODMComment[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ODMComment[]|Proxy[] createSequence(iterable|callable $sequence)
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
            'body' => self::faker()->sentence(),
            'user' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            ->withoutPersisting()
            // ->afterInstantiate(function(ODMComment $oDMComment): void {})
        ;
    }

    protected static function getClass(): string
    {
        return ODMComment::class;
    }
}
