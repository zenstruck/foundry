<?php

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Story
{
    /** @var array<string, Proxy> */
    private array $objects = [];

    private function __construct()
    {
    }

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
        if (StoryManager::has(static::class)) {
            return StoryManager::get(static::class);
        }

        $story = new static();
        $story->build();

        StoryManager::set($story);

        return $story;
    }

    final public function add(string $name, object $object): self
    {
        // ensure factories are persisted
        if ($object instanceof Factory) {
            $object = $object->create();
        }

        // ensure all objects are wrapped in a auto-refreshing proxy
        $this->objects[$name] = PersistenceManager::proxy($object)->withAutoRefresh();

        return $this;
    }

    final public function get(string $name): Proxy
    {
        if (!\array_key_exists($name, $this->objects)) {
            throw new \InvalidArgumentException('explain that object was not registered'); // todo
        }

        return $this->objects[$name];
    }

    abstract protected function build(): void;
}
