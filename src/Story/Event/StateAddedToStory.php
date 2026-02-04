<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Story\Event;

/**
 * @internal
 *
 * @template T of object
 */
final class StateAddedToStory
{
    public function __construct(
        public readonly object $object,
        public readonly string $name,
    ) {
    }
}
