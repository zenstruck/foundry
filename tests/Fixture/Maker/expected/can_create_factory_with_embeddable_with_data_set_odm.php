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
use Zenstruck\Foundry\Tests\Fixture\Document\WithEmbeddableDocument;

/**
 * @extends PersistentProxyObjectFactory<WithEmbeddableDocument>
 *
 * @method        WithEmbeddableDocument|Proxy                create(array|callable $attributes = [])
 * @method static WithEmbeddableDocument|Proxy                createOne(array $attributes = [])
 * @method static WithEmbeddableDocument|Proxy                find(object|array|mixed $criteria)
 * @method static WithEmbeddableDocument|Proxy                findOrCreate(array $attributes)
 * @method static WithEmbeddableDocument|Proxy                first(string $sortedField = 'id')
 * @method static WithEmbeddableDocument|Proxy                last(string $sortedField = 'id')
 * @method static WithEmbeddableDocument|Proxy                random(array $attributes = [])
 * @method static WithEmbeddableDocument|Proxy                randomOrCreate(array $attributes = [])
 * @method static DocumentRepository|ProxyRepositoryDecorator repository()
 * @method static WithEmbeddableDocument[]|Proxy[]            all()
 * @method static WithEmbeddableDocument[]|Proxy[]            createMany(int $number, array|callable $attributes = [])
 * @method static WithEmbeddableDocument[]|Proxy[]            createSequence(iterable|callable $sequence)
 * @method static WithEmbeddableDocument[]|Proxy[]            findBy(array $attributes)
 * @method static WithEmbeddableDocument[]|Proxy[]            randomRange(int $min, int $max, array $attributes = [])
 * @method static WithEmbeddableDocument[]|Proxy[]            randomSet(int $number, array $attributes = [])
 */
final class WithEmbeddableDocumentFactory extends PersistentProxyObjectFactory
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
        return WithEmbeddableDocument::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'embeddable' => EmbeddableFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WithEmbeddableDocument $withEmbeddableDocument): void {})
        ;
    }
}
