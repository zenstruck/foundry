<?php

namespace Zenstruck\Foundry\Tests\Fixture;

final class ObjectWithEnum
{
    public function __construct(
        public readonly SomeEnum $someEnum
    )
    {
    }
}
