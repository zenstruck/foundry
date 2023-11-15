<?php

namespace Zenstruck\Callback\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnresolveableArgument extends \RuntimeException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
