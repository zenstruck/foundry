<?php

namespace Zenstruck\Foundry\Exception;

/**
 * @internal
 */
final class FoundryNotBootedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Foundry is not yet booted. Using in a test: is your Test case using the Factories trait? Using in a fixture: is ZenstruckFoundryBundle enabled for this environment?'
        );
    }
}
