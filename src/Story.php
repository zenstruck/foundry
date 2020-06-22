<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Story
{
    /** @var array<string, Proxy> */
    private array $objects = [];

    final public function __call(string $method, array $arguments)
    {
        return $this->get($method);
    }

    public static function __callStatic($name, $arguments)
    {
        return static::load()->get($name);
    }

    final public static function load(): self
    {
        return Factory::configuration()->stories()->load(static::class);
    }

    final public function add(string $name, object $object): self
    {
        // ensure factories are persisted
        if ($object instanceof Factory) {
            $object = $object->create();
        }

        // ensure objects are proxied
        if (!$object instanceof Proxy) {
            $object = new Proxy($object);
        }

        // ensure proxies are persisted
        if (!$object->isPersisted()) {
            $object->save();
        }

        $this->objects[$name] = $object;

        return $this;
    }

    final public function get(string $name): Proxy
    {
        if (!\array_key_exists($name, $this->objects)) {
            throw new \InvalidArgumentException('explain that object was not registered'); // todo
        }

        return $this->objects[$name];
    }

    abstract public function build(): void;
}
