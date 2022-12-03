<?php

namespace Zenstruck\Foundry;

/**
 * @template Ttype
 */
class LazyValue
{
    /** @var callable(array):Ttype */
    private $cb;

    /**
     * @param callable(array):Ttype $cb
     */
    public static function with(callable $cb): self
    {
        return new self($cb);
    }

    private function __construct(callable $cb)
    {
        $this->cb = $cb;
    }

    /**
     * @return Ttype
     */
    public function __invoke(array $attributes)
    {
        $cb = $this->cb;
        return $cb($attributes);
    }
}
