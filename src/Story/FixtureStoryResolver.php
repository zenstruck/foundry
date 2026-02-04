<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Story;

use Zenstruck\Foundry\Story;

/**
 * @internal
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FixtureStoryResolver
{
    public function __construct(
        /** @var array<string, class-string<Story>> */
        private readonly array $fixtureStories,
        /** @var array<string, array<string, class-string<Story>>> */
        private readonly array $groupedStories = [],
    ) {
    }

    /**
     * @return array<string, class-string<Story>>
     *
     * @throws FixtureStoryNotFound
     */
    public function resolve(string $fixtureOrGroupName): array
    {
        if ($this->hasFixture($fixtureOrGroupName)) {
            return [$fixtureOrGroupName => $this->fixtureStories[$fixtureOrGroupName]];
        }

        if ($this->hasGroup($fixtureOrGroupName)) {
            return $this->resolveGroup($fixtureOrGroupName);
        }

        throw FixtureStoryNotFound::forNameOrGroup(
            $fixtureOrGroupName,
            [...$this->availableFixtureNames(), ...$this->availableGroupNames()]
        );
    }

    public function hasAnyFixtures(): bool
    {
        return count($this->fixtureStories) > 0;
    }

    public function hasFixture(string $name): bool
    {
        return isset($this->fixtureStories[$name]);
    }

    public function hasOnlyOneFixture(): bool
    {
        return count($this->fixtureStories) === 1;
    }

    /**
     * @return list<string>
     */
    public function availableFixtureNames(): array
    {
        return \array_keys($this->fixtureStories);
    }

    /**
     * @return list<string>
     */
    public function availableGroupNames(): array
    {
        return \array_keys($this->groupedStories);
    }

    /**
     * @return array<string, class-string<Story>>
     *
     * @throws FixtureStoryNotFound
     */
    private function resolveGroup(string $groupName): array
    {
        if (!isset($this->groupedStories[$groupName])) {
            throw FixtureStoryNotFound::forGroup($groupName, $this->availableGroupNames());
        }

        return $this->groupedStories[$groupName];
    }

    private function hasGroup(string $name): bool
    {
        return isset($this->groupedStories[$name]);
    }
}
