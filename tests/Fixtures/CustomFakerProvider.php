<?php

namespace Zenstruck\Foundry\Tests\Fixtures;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomFakerProvider
{
    public function customValue(): string
    {
        return 'custom-value';
    }
}
