<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\Exception;

final class NoPersistenceStrategy extends \LogicException
{
    /**
     * @param class-string $class
     */
    public function __construct(string $class, \Throwable|null $previous = null)
    {
        parent::__construct(
            \sprintf('No persistence strategy found for "%s".', $class),
            previous: $previous
        );
    }
}
