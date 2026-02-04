<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use Yceruto\BehatExtension\Context\ExceptionAssertionTrait;
use Zenstruck\Foundry\Test\Behat\FoundryContext;

final class TestFoundryContext extends FoundryContext // @phpstan-ignore class.extendsFinalByPhpDoc
{
    use ExceptionAssertionTrait;
}
