<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Zenstruck\Foundry\ModelFactory;

/**
 * @internal
 */
final class FactoryFinder
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
                    $carry[\get_class($factory)] = $factory::getEntityClass();

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
}
