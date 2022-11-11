<?php

namespace Zenstruck\Foundry;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryManager
{
    /** @var array<string, Story> */
    private static array $globalInstances = [];

    /** @var array<string, Story> */
    private static array $instances = [];

    /**
     * @param Story[] $stories
     */
    public function __construct(private iterable $stories)
    {
    }

    public function load(string $class): Story
    {
        if (\array_key_exists($class, self::$globalInstances)) {
            return self::$globalInstances[$class];
        }

        if (\array_key_exists($class, self::$instances)) {
            return self::$instances[$class];
        }

        $story = $this->getOrCreateStory($class);
        $story->build();

        return self::$instances[$class] = $story;
    }

    public static function setGlobalState(): void
    {
        self::$globalInstances = self::$instances;
        self::$instances = [];
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    public static function globalReset(): void
    {
        self::$globalInstances = [];
        self::$instances = [];
    }

    private function getOrCreateStory(string $class): Story
    {
        foreach ($this->stories as $story) {
            if ($class === $story::class) {
                return $story;
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Stories with dependencies (Story services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
