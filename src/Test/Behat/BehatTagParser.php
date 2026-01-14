<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

/**
 * @internal
 *
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BehatTagParser
{
    private const FIXTURE_TAG_PATTERN = '/^withFixture\(([^)]+)\)$/';

    /**
     * @param list<string> $tags
     *
     * @return ?string
     */
    public function parseFixtureName(array $tags): ?string
    {
        $fixtureNames = [];

        foreach ($tags as $tag) {
            if (\preg_match(self::FIXTURE_TAG_PATTERN, $tag, $matches)) {
                $fixtureNames[] = $matches[1];
            }
        }

        if (0 === \count($fixtureNames)) {
            return null;
        }

        if (\count($fixtureNames) > 1) {
            throw new \RuntimeException('Multiple @withFixture tags found: you can only load one fixture per scenario.');
        }

        return $fixtureNames[0];
    }
}
