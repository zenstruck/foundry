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

/**
 * @internal
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FixtureStoryNotFound extends \RuntimeException
{
    /**
     * @param list<string> $availableFixtures
     */
    public static function forNameOrGroup(string $fixtureName, array $availableFixtures): self
    {
        $message = "Fixture story with name or group \"{$fixtureName}\" not found:";

        if ($availableFixtures) {
            $message .= ' Available fixtures: '.\implode(', ', $availableFixtures);
        } else {
            $message .= ' No fixture stories are registered. Add #[AsFixture] attribute to your Story classes.';
        }

        return new self($message);
    }

    /**
     * @param list<string> $availableGroups
     */
    public static function forGroup(string $groupName, array $availableGroups): self
    {
        $message = "Fixture story group \"{$groupName}\" not found:";

        if ($availableGroups) {
            $message .= ' Available groups: '.\implode(', ', $availableGroups);
        } else {
            $message .= ' No fixture story groups are registered.';
        }

        return new self($message);
    }
}
