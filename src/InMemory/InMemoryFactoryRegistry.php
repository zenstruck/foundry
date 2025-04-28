<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\InMemory;

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryRegistryInterface;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class InMemoryFactoryRegistry implements FactoryRegistryInterface
{
    public function __construct(
        private readonly FactoryRegistryInterface $decorated,
    ) {
    }

    /**
     * @template T of Factory
     *
     * @param class-string<T> $class
     *
     * @return T
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

        return $factory // @phpstan-ignore argument.templateType
            ->afterInstantiate(
                function(object $object) use ($factory) {
                    Configuration::instance()->inMemoryRepositoryRegistry?->get($factory::class())->_save($object);
                }
            );
    }
}
