<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryCollection
{
    /** @var Factory */
    private $factory;

    /** @var int */
    private $min;

    /** @var int */
    private $max;

    /**
     * @param int|null $max If set, when created, the collection will be a random size between $min and $max
     */
    public function __construct(Factory $factory, int $min, ?int $max = null)
    {
        if ($max && $min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        $this->factory = $factory;
        $this->min = $min;
        $this->max = $max ?? $min;
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy[]|object[]
     */
    public function create($attributes = []): array
    {
        return \array_map(
            static function(Factory $factory) use ($attributes) {
                return $factory->create($attributes);
            },
            $this->all()
        );
    }

    /**
     * @return Factory[]
     */
    public function all(): array
    {
        return \array_map(
            function() {
                return clone $this->factory;
            },
            \array_fill(0, \random_int($this->min, $this->max), null)
        );
    }

    public function factory(): Factory
    {
        return $this->factory;
    }
}
