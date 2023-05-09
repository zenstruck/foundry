<?php

namespace App\Factory;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\DocumentWithEnum;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\SomeEnum;

/**
 * @extends PersistentObjectFactory<DocumentWithEnum>
 *
 * @method        DocumentWithEnum|Proxy create(array|callable $attributes = [])
 * @method static DocumentWithEnum|Proxy createOne(array $attributes = [])
 * @method static DocumentWithEnum|Proxy find(object|array|mixed $criteria)
 * @method static DocumentWithEnum|Proxy findOrCreate(array $attributes)
 * @method static DocumentWithEnum|Proxy first(string $sortedField = 'id')
 * @method static DocumentWithEnum|Proxy last(string $sortedField = 'id')
 * @method static DocumentWithEnum|Proxy random(array $attributes = [])
 * @method static DocumentWithEnum|Proxy randomOrCreate(array $attributes = [])
 * @method static DocumentRepository|RepositoryProxy repository()
 * @method static DocumentWithEnum[]|Proxy[] all()
 * @method static DocumentWithEnum[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static DocumentWithEnum[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static DocumentWithEnum[]|Proxy[] findBy(array $attributes)
 * @method static DocumentWithEnum[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static DocumentWithEnum[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class DocumentWithEnumFactory extends PersistentObjectFactory
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
            'enum' => self::faker()->randomElement(SomeEnum::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(DocumentWithEnum $documentWithEnum): void {})
        ;
    }

    public static function class(): string
    {
        return DocumentWithEnum::class;
    }
}
