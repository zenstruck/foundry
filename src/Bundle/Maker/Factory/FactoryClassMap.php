<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Zenstruck\Foundry\Bundle\Maker\Factory\Exception\FactoryClassAlreadyExistException;
use Zenstruck\Foundry\ModelFactory;

/**
 * @internal
 */
final class FactoryClassMap
{
    /**
     * @var array<class-string, class-string> factory classes as keys, object class as values
     */
    private array $classesWithFactories;

    /** @param \Traversable<ModelFactory> $factories */
    public function __construct(\Traversable $factories)
    {
        /** @phpstan-ignore-next-line */
        $this->classesWithFactories = \array_unique(
            \array_reduce(
                \iterator_to_array($factories, preserve_keys: true),
                static function(array $carry, ModelFactory $factory): array {
                    $carry[$factory::class] = $factory::getEntityClass();

                    return $carry;
                },
                []
            )
        );
    }

    /** @param class-string $class */
    public function classHasFactory(string $class): bool
    {
        return \in_array($class, $this->classesWithFactories, true);
    }

    /**
     * @param class-string $class
     *
     * @return class-string|null
     */
    public function getFactoryForClass(string $class): ?string
    {
        $factories = \array_flip($this->classesWithFactories);

        return $factories[$class] ?? null;
    }

    /**
     * @param class-string $factoryClass
     * @param class-string $class
     */
    public function addFactoryForClass(string $factoryClass, string $class): void
    {
        if (\array_key_exists($factoryClass, $this->classesWithFactories)) {
            throw new FactoryClassAlreadyExistException($factoryClass);
        }

        $this->classesWithFactories[$factoryClass] = $class;
    }

    public function factoryClassExists(string $factoryClass): bool
    {
        return \array_key_exists($factoryClass, $this->classesWithFactories);
    }
}
