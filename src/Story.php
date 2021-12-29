<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Story
{
    /** @var array<string, mixed> */
    private $state = [];

    final public function __call(string $method, array $arguments)
    {
        return $this->get($method);
    }

    public static function __callStatic($name, $arguments)
    {
        return static::load()->get($name);
    }

    /**
     * @return static
     */
    final public static function load(): self
    {
        return Factory::configuration()->stories()->load(static::class);
    }

    /**
     * @return static
     */
    final public function add(string $name, $state): self
    {
        if (\is_object($state)) {
            // ensure factories are persisted
            if ($state instanceof Factory) {
                $state = $state->create();
            }

            // ensure objects are proxied
            if (!$state instanceof Proxy) {
                $state = new Proxy($state);
            }

            // ensure proxies are persisted
            if (!$state->isPersisted()) {
                $state->save();
            }
        }

        $this->state[$name] = $state;

        return $this;
    }

    final public function get(string $name): Proxy
    {
        if (!\array_key_exists($name, $this->state)) {
            throw new \InvalidArgumentException(\sprintf('"%s" was not registered. Did you forget to call "%s::add()"?', $name, static::class));
        }

        return $this->state[$name];
    }

    abstract public function build(): void;
}
