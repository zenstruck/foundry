<?php

namespace Zenstruck\Foundry\Utils\Rector\RemoveFunctionCall;

final class RemoveFunctionCall
{
    public function __construct(
        public readonly string $functionName,
    ) {
    }
}
