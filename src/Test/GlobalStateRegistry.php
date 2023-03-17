<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use Zenstruck\Foundry\Story;

/**
 * @internal
 */
final class GlobalStateRegistry
{
    /** @var list<Story> */
    private array $storiesAsService = [];

    /** @var list<callable> */
    private array $invokableServices = [];

    /** @var list<class-string<Story>> */
    private array $standaloneStories = [];

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
        return [
            ...\array_map(
                static fn(Story $story): \Closure => static function() use ($story): void {
                    // even if we already have access to the instance here,
                    // let's use ::load() in order to register the service in StoryManager
                    $story::class::load();
                },
                $this->storiesAsService
            ),
            ...$this->invokableServices,
            ...\array_map(
                static fn(string $storyClassName): \Closure => static function() use ($storyClassName): void {
                    $storyClassName::load();
                },
                $this->standaloneStories
            ),
        ];
    }
}
