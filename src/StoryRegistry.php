<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StoryRegistry
{
    /** @var array<string,Story> */
    private static array $globalInstances = [];

    /** @var array<string,Story> */
    private static array $instances = [];

    /**
     * @param Story[]                                   $stories
     * @param list<class-string<Story>|callable():void> $globalStories
     */
    public function __construct(private iterable $stories, private array $globalStories = [])
    {
    }

    /**
     * @template T of Story
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function load(string $class): Story
    {
        if (\array_key_exists($class, self::$globalInstances)) {
            return self::$globalInstances[$class]; // @phpstan-ignore-line
        }

        if (\array_key_exists($class, self::$instances)) {
            return self::$instances[$class]; // @phpstan-ignore-line
        }

        self::$instances[$class] = $this->getOrCreateStory($class);
        self::$instances[$class]->build();

        return self::$instances[$class];
    }

    public function loadGlobalStories(): void
    {
        self::$globalInstances = [];

        foreach ($this->globalStories as $story) {
            \is_a($story, Story::class, true) ? $this->load($story) : $story(); // @phpstan-ignore-line
        }

        self::$globalInstances = self::$instances;
        self::$instances = [];
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    /**
     * @template T of Story
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function getOrCreateStory(string $class): Story
    {
        foreach ($this->stories as $story) {
            if ($class === $story::class) {
                return $story; // @phpstan-ignore-line
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) { // @phpstan-ignore-line
            throw new \RuntimeException('Stories with dependencies (Story services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
