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

    /**
     * Stories shared by several tests (a class, or the data sets of a test method), innermost scope last.
     *
     * @var list<array{scope: string, instances: array<string,Story>}>
     */
    private static array $sharedInstances = [];

    /** @var array<string,Story> */
    private static array $instances = [];

    /**
     * @param Story[]                                   $stories
     * @param list<class-string<Story>|callable():void> $globalStories
     */
    public function __construct(private iterable $stories, private array $globalStories = [], private bool $bundleEnabled = false)
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
        // Global stories hold objects tied to the persistence layer they were built with:
        // when persistence is not available (ie: in a unit test), rebuild the story locally instead.
        if (\array_key_exists($class, self::$globalInstances) && Configuration::instance()->isPersistenceAvailable()) {
            return self::$globalInstances[$class]; // @phpstan-ignore return.type
        }

        // shared stories live in the database: an in-memory test builds its own
        if (!Configuration::instance()->isInMemoryEnabled()) {
            foreach (self::$sharedInstances as ['instances' => $instances]) {
                if (\array_key_exists($class, $instances)) {
                    return $instances[$class]; // @phpstan-ignore return.type
                }
            }
        }

        if (\array_key_exists($class, self::$instances)) {
            return self::$instances[$class]; // @phpstan-ignore return.type
        }

        self::$instances[$class] = $this->getOrCreateStory($class);
        self::$instances[$class]->build();

        return self::$instances[$class];
    }

    public function loadGlobalStories(): void
    {
        self::$globalInstances = [];

        foreach ($this->globalStories as $story) {
            \is_a($story, Story::class, true) ? $this->load($story) : $story(); // @phpstan-ignore argument.type, argument.type
        }

        self::$globalInstances = self::$instances;
        self::$instances = [];
    }

    /**
     * Loads stories once for all the tests of a scope: they are kept until releaseSharedStories() is called for it.
     *
     * @param list<class-string<Story>> $stories
     */
    public function loadSharedStories(string $scope, array $stories): void
    {
        foreach ($stories as $story) {
            $this->load($story);
        }

        self::$sharedInstances[] = ['scope' => $scope, 'instances' => self::$instances];
        self::$instances = [];
    }

    public static function hasSharedStories(string $scope): bool
    {
        return \in_array($scope, \array_column(self::$sharedInstances, 'scope'), true);
    }

    /**
     * @return bool whether the innermost shared stories were the ones of this scope
     */
    public static function releaseSharedStories(string $scope): bool
    {
        $innermost = \array_key_last(self::$sharedInstances);

        if (null === $innermost || $scope !== self::$sharedInstances[$innermost]['scope']) {
            return false;
        }

        \array_pop(self::$sharedInstances);

        return true;
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
                return $story; // @phpstan-ignore return.type
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            if ($this->bundleEnabled) {
                throw new \RuntimeException(\sprintf('Story "%s" has dependencies, so it must be a service tagged with "foundry.story". Register it as a service with autoconfiguration enabled, or add the tag yourself.', $class), 0, $e);
            }

            throw new \RuntimeException('Stories with dependencies (Story services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
