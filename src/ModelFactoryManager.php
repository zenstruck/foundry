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

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Model Factories with dependencies (Model Factory services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
