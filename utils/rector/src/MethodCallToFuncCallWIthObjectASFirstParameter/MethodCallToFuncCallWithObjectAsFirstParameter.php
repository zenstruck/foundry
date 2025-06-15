<?php

namespace Zenstruck\Foundry\Utils\Rector\MethodCallToFuncCallWIthObjectASFirstParameter;

final class MethodCallToFuncCallWithObjectAsFirstParameter
{
    public function __construct(
        public readonly string $methodName,
        public readonly string $functionName
    ) {
    }
}
