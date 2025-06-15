<?php

namespace Zenstruck\Foundry\Utils\Rector\RemoveMethodCall;

final class RemoveMethodCall
{
    public function __construct(
        public readonly string $methodName,
    ) {
    }
}
