<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Test;

use Zenstruck\Foundry\Story;

/**
 * @internal
 */
final class GlobalStateRegistry
{
    /** @var list<Story> */
    private $storiesAsService = [];

    /** @var list<callable> */
    private $invokableServices = [];

    /** @var list<class-string<Story>> */
    private $standaloneStories = [];

    public function addStoryAsService(Story $storyAsService): void
    {
        $this->storiesAsService[] = $storyAsService;
    }

    public function addInvokableService(callable $invokableService): void
    {
        $this->invokableServices[] = $invokableService;
    }

    /**
     * @param class-string<Story> $storyClass
     */
    public function addStandaloneStory(string $storyClass): void
    {
        $this->standaloneStories[] = $storyClass;
    }

    /**
     * @return list<callable>
     */
    public function getGlobalStates(): array
    {
        return \array_merge(
            \array_map(
                static function(Story $story) {
                    return static function() use ($story) {$story->build(); };
                },
                $this->storiesAsService
            ),
            $this->invokableServices,
            \array_map(
                static function(string $storyClassName) {
                    return static function() use ($storyClassName) {$storyClassName::load(); };
                },
                $this->standaloneStories
            )
        );
    }
}
