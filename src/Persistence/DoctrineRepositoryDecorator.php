<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;

/**
 * Everything that only makes sense against a Doctrine object manager: the Doctrine repository
 * interface, access to the underlying repository and its own methods, lookup by a bare identifier,
 * and counting with a query instead of loading every object.
 *
 * @template T of object
 * @template I of ObjectRepository
 * @extends RepositoryDecorator<T>
 * @implements I<T>
 * @mixin I
 *
 * @phpstan-import-type Parameters from \Zenstruck\Foundry\Factory
 */
class DoctrineRepositoryDecorator extends RepositoryDecorator implements ObjectRepository
{
    /**
     * @param mixed[] $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->inner()->{$name}(...$arguments);
    }

    /**
     * @phpstan-param Parameters $criteria
     */
    public function count(array $criteria = []): int
    {
        $inner = $this->inner();

        if ($inner instanceof EntityRepository) {
            // use query to avoid loading all entities
            return $inner->count($this->normalize($criteria));
        }

        return parent::count($criteria);
    }

    /**
     * @return ObjectRepository<T>
     */
    public function inner(): ObjectRepository
    {
        $strategy = Configuration::instance()->persistence()->strategyFor($this->class);
        \assert($strategy instanceof DoctrinePersistenceStrategy);

        return $strategy->objectManagerFor($this->class)->getRepository($this->class);
    }

    /**
     * @return T|null
     */
    protected function findByIdentifier(mixed $id): ?object
    {
        /** @var T|null $object */
        $object = $this->inner()->find($id);

        if ($object) {
            Configuration::instance()->persistedObjectsTracker?->add($object);
        }

        return $object;
    }
}
