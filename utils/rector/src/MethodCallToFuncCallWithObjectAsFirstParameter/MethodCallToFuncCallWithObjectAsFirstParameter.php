<?php

namespace Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWithObjectAsFirstParameter;

final class MethodCallToFuncCallWithObjectAsFirstParameter
{
    public function __construct(
        public readonly string $methodName,
        public readonly string $functionName
    ) {
    }
}
