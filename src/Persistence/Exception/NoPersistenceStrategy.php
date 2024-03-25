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

namespace Zenstruck\Foundry\Persistence\Exception;

final class NoPersistenceStrategy extends \LogicException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct(
            \sprintf('No persistence strategy found for "%s".', $class),
            previous: $previous
        );
    }
}
