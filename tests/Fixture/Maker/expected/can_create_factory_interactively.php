<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory\Contact;

use App\Factory\Address\StandardAddressFactory;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;

/**
 * @extends PersistentProxyObjectFactory<StandardContact>
 *
 * @method        StandardContact|Proxy                     create(array|callable $attributes = [])
 * @method static StandardContact|Proxy                     createOne(array $attributes = [])
 * @method static StandardContact|Proxy                     find(object|array|mixed $criteria)
 * @method static StandardContact|Proxy                     findOrCreate(array $attributes)
 * @method static StandardContact|Proxy                     first(string $sortedField = 'id')
 * @method static StandardContact|Proxy                     last(string $sortedField = 'id')
 * @method static StandardContact|Proxy                     random(array $attributes = [])
 * @method static StandardContact|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static StandardContact[]|Proxy[]                 all()
 * @method static StandardContact[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static StandardContact[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static StandardContact[]|Proxy[]                 findBy(array $attributes)
 * @method static StandardContact[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static StandardContact[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class StandardContactFactory extends PersistentProxyObjectFactory
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
        return StandardContact::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'address' => StandardAddressFactory::new(),
            'name' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(StandardContact $standardContact): void {})
        ;
    }
}
