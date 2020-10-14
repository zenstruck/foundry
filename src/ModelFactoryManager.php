<?php

namespace Zenstruck\Foundry;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryManager
{
    private $factories;

    /**
     * @param ModelFactory[] $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    public function create(string $class): ModelFactory
    {
        foreach ($this->factories as $factory) {
            if ($class === \get_class($factory)) {
                return $factory;
            }
        }

        return new $class();
    }
}
