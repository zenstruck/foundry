<?php

namespace App\Factory;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;

/**
 * @extends PersistentObjectFactory<Contact>
 *
 * @method        Contact|Proxy create(array|callable $attributes = [])
 * @method static Contact|Proxy createOne(array $attributes = [])
 * @method static Contact|Proxy find(object|array|mixed $criteria)
 * @method static Contact|Proxy findOrCreate(array $attributes)
 * @method static Contact|Proxy first(string $sortedField = 'id')
 * @method static Contact|Proxy last(string $sortedField = 'id')
 * @method static Contact|Proxy random(array $attributes = [])
 * @method static Contact|Proxy randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Contact[]|Proxy[] all()
 * @method static Contact[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Contact[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Contact[]|Proxy[] findBy(array $attributes)
 * @method static Contact[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Contact[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class ContactFactory extends PersistentObjectFactory
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
            'address' => AddressFactory::new(),
            'name' => self::faker()->text(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Contact $contact): void {})
        ;
    }

    public static function class(): string
    {
        return Contact::class;
    }
}
