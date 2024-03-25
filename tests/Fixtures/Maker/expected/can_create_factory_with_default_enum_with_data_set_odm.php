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

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\DocumentWithEnum;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\SomeEnum;

/**
 * @extends PersistentProxyObjectFactory<DocumentWithEnum>
 *
 * @method        DocumentWithEnum|Proxy                      create(array|callable $attributes = [])
 * @method static DocumentWithEnum|Proxy                      createOne(array $attributes = [])
 * @method static DocumentWithEnum|Proxy                      find(object|array|mixed $criteria)
 * @method static DocumentWithEnum|Proxy                      findOrCreate(array $attributes)
 * @method static DocumentWithEnum|Proxy                      first(string $sortedField = 'id')
 * @method static DocumentWithEnum|Proxy                      last(string $sortedField = 'id')
 * @method static DocumentWithEnum|Proxy                      random(array $attributes = [])
 * @method static DocumentWithEnum|Proxy                      randomOrCreate(array $attributes = [])
 * @method static DocumentRepository|ProxyRepositoryDecorator repository()
 * @method static DocumentWithEnum[]|Proxy[]                  all()
 * @method static DocumentWithEnum[]|Proxy[]                  createMany(int $number, array|callable $attributes = [])
 * @method static DocumentWithEnum[]|Proxy[]                  createSequence(iterable|callable $sequence)
 * @method static DocumentWithEnum[]|Proxy[]                  findBy(array $attributes)
 * @method static DocumentWithEnum[]|Proxy[]                  randomRange(int $min, int $max, array $attributes = [])
 * @method static DocumentWithEnum[]|Proxy[]                  randomSet(int $number, array $attributes = [])
 */
final class DocumentWithEnumFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return DocumentWithEnum::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'enum' => self::faker()->randomElement(SomeEnum::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(DocumentWithEnum $documentWithEnum): void {})
        ;
    }
}
