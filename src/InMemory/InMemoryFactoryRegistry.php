<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory;

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryRegistryInterface;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @internal
 * @template T of object
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryFactoryRegistry implements FactoryRegistryInterface
{
    public function __construct(
        private readonly FactoryRegistryInterface $decorated,
    ) {
    }

    /**
     * @template TFactory of Factory
     *
     * @param class-string<TFactory> $class
     *
     * @return TFactory
     */
    public function get(string $class): Factory
    {
        $factory = $this->decorated->get($class);

        if (!$factory instanceof ObjectFactory || !Configuration::instance()->isInMemoryEnabled()) {
            return $factory;
        }

        if ($factory instanceof PersistentObjectFactory) {
            $factory = $factory->withoutPersisting();
        }

        return $factory
            ->afterInstantiate(
                function (object $object) use ($factory) {
                    Configuration::instance()->inMemoryRepositoryRegistry?->get($factory::class())->_save($object);
                }
            );
    }
}
