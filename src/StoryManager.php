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
 * @internal
 * @template T of Story
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class StoryManager
{
    /** @var array<string, T> */
    private static array $globalInstances = [];

    /** @var array<string, T> */
    private static array $instances = [];

    /**
     * @param T[] $stories
     */
    public function __construct(private iterable $stories)
    {
    }

    /**
     * @param class-string<T> $class
     *
     * @return T
     */
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

    /**
     * @param class-string<T> $class
     *
     * @return T
     */
    private function getOrCreateStory(string $class): Story
    {
        foreach ($this->stories as $story) {
            if ($class === $story::class) {
                return $story;
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) { // @phpstan-ignore-line
            throw new \RuntimeException('Stories with dependencies (Story services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
